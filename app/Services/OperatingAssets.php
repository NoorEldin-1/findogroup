<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingAssets
{
    /**
     * Asset states in which nothing moves any more.
     */
    public const OUT_OF_SERVICE = ['retired', 'completed', 'archived'];

    /**
     * Roles told about low stock and expiring warranties.
     */
    public const STOCK_RECIPIENTS = ['owner', 'finance', 'hr'];

    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow, private OperatingNotifier $notifier, private OperatingAccess $access, private OperatingSales $sales) {}

    /**
     * Runs inside the movement completion transaction: custody, status and condition of the asset change only here,
     * so the asset's history is the list of its completed movements.
     *
     * @return array<string, mixed> the movement data with the previous holder recorded
     */
    public function applyMovement(object $movement, User $user, string $reason): array
    {
        $data = $this->catalog->data($movement);
        $asset = DB::table('operating_records')->where('id', $data['asset_id'])->lockForUpdate()->first();
        $assetData = $this->catalog->data($asset);
        $holder = (int) ($assetData['assignee_id'] ?? 0);
        $to = (int) ($data['to_employee_id'] ?? 0);
        $kind = $data['movement'];
        if (in_array($asset->status, self::OUT_OF_SERVICE, true)) {
            $this->workflow->fail('الأصل خارج الخدمة؛ لا تُسجَّل عليه حركات.', 'The asset is out of service; it cannot move.');
        }
        if (in_array($kind, ['assign', 'transfer'], true) && $asset->status === 'maintenance') {
            $this->workflow->fail('الأصل في الصيانة. سجّل عودته أولًا.', 'The asset is in maintenance. Record its return first.');
        }
        match ($kind) {
            'assign' => $holder ? $this->workflow->fail('الأصل مسلَّم بالفعل لموظف. استخدم النقل.', 'The asset is already handed over. Use a transfer.') : null,
            'transfer' => ! $holder ? $this->workflow->fail('الأصل غير مسلَّم لأحد. استخدم التسليم.', 'Nobody holds the asset. Use a handover.') : ($to === $holder ? $this->workflow->fail('الأصل مع هذا الموظف بالفعل.', 'This employee already holds the asset.') : null),
            'return' => ! $holder ? $this->workflow->fail('الأصل غير مسلَّم لأحد.', 'Nobody holds the asset.') : null,
            'maintenance' => $asset->status === 'maintenance' ? $this->workflow->fail('الأصل في الصيانة بالفعل.', 'The asset is already in maintenance.') : null,
            'maintenance_done' => $asset->status !== 'maintenance' ? $this->workflow->fail('الأصل ليس في الصيانة.', 'The asset is not in maintenance.') : null,
        };
        $after = $assetData;
        $status = $asset->status;
        if (in_array($kind, ['assign', 'transfer'], true)) {
            $after['assignee_id'] = $to;
            $status = $status === 'draft' ? 'active' : $status;
        }
        if ($kind === 'return') {
            $after['assignee_id'] = null;
        }
        if ($kind === 'maintenance') {
            $after['status_before_maintenance'] = $status;
            $status = 'maintenance';
        }
        if ($kind === 'maintenance_done') {
            $status = $after['status_before_maintenance'] ?? 'active';
            unset($after['status_before_maintenance']);
        }
        if (! empty($data['condition'])) {
            $after['condition'] = $data['condition'];
        }
        DB::table('operating_records')->where('id', $asset->id)->update(['status' => $status, 'data' => json_encode($after, JSON_UNESCAPED_UNICODE), 'version' => $asset->version + 1, 'updated_at' => now()]);
        $this->workflow->event(DB::table('operating_records')->find($asset->id), 'update', 'Asset movement "'.$movement->title.'" ('.$kind.'): '.$reason, ['status' => $asset->status, 'assignee_id' => $holder ?: null], ['status' => $status, 'assignee_id' => $after['assignee_id'] ?? null], $user->id);

        return array_merge($data, ['from_employee_id' => $holder ?: null]);
    }

    /**
     * Runs inside the stock movement completion transaction: the item quantity changes only through completed movements.
     *
     * @return array<string, mixed> the movement data with the quantity before and after
     */
    public function applyStock(object $movement, User $user, string $reason): array
    {
        $data = $this->catalog->data($movement);
        $item = DB::table('operating_records')->where('id', $data['item_id'])->lockForUpdate()->first();
        if ($item->status !== 'active') {
            $this->workflow->fail('الصنف غير نشط.', 'The item is not active.');
        }
        $itemData = $this->catalog->data($item);
        $before = (float) ($itemData['quantity'] ?? 0);
        $quantity = (float) $data['quantity'];
        $after = round(match ($data['movement']) {
            'in' => $before + $quantity,
            'out' => $before - $quantity,
            'adjust' => $quantity,
        }, 3);
        if ($after < 0) {
            $this->workflow->fail('الكمية المتاحة لا تكفي (المتاح '.$before.').', 'Not enough stock (available '.$before.').');
        }
        DB::table('operating_records')->where('id', $item->id)->update(['data' => json_encode(array_merge($itemData, ['quantity' => $after]), JSON_UNESCAPED_UNICODE), 'version' => $item->version + 1, 'updated_at' => now()]);
        $this->workflow->event(DB::table('operating_records')->find($item->id), 'update', 'Stock movement "'.$movement->title.'" ('.$data['movement'].'): '.$reason, ['quantity' => $before], ['quantity' => $after], $user->id);

        return array_merge($data, ['quantity_before' => $before, 'quantity_after' => $after]);
    }

    /**
     * @return array{quantity: float, min: float, low: bool}
     */
    public function stockLevel(object $item): array
    {
        $data = $this->catalog->data($item);
        $quantity = (float) ($data['quantity'] ?? 0);
        $min = (float) ($data['min_quantity'] ?? 0);

        return ['quantity' => $quantity, 'min' => $min, 'low' => $item->status === 'active' && $quantity <= $min];
    }

    /**
     * One alert each time an item drops to its reorder level; restocking above the level re-arms it.
     */
    public function checkStock(object $item): bool
    {
        $item = DB::table('operating_records')->find($item->id);
        $level = $this->stockLevel($item);
        $alerted = ! empty($this->catalog->data($item)['low_stock_alerted']);
        if ($level['low'] && ! $alerted) {
            $this->notifier->send($this->notifier->roles(self::STOCK_RECIPIENTS)->all(), new OperatingAlert(
                'low_stock',
                ['ar' => 'مخزون منخفض', 'en' => 'Low stock'],
                ['ar' => 'الصنف "'.$item->title.'" وصل إلى '.$level['quantity'].' (حد إعادة الطلب '.$level['min'].'). اطلب كمية جديدة.', 'en' => 'The item "'.$item->title.'" is at '.$level['quantity'].' (reorder level '.$level['min'].'). Reorder it.'],
                '/admin/operations/record/'.$item->id,
            ));
            $this->workflow->mark($item, 'low_stock_alerted', today()->toDateString(), 'Low stock alert sent');

            return true;
        }
        if (! $level['low'] && $alerted) {
            $this->workflow->mark($item, 'low_stock_alerted', '', 'Stock back above the reorder level');
        }

        return false;
    }

    /**
     * The register at a glance for asset managers: where things are, what is out of action, and what needs buying or renewing.
     *
     * @return array{by_status: array<string, int>, by_category: array<string, int>, custody: list<array{employee: ?string, employee_id: int, count: int}>, maintenance: list<object>, warranty: list<array{id: int, title: string, tag: ?string, warranty_end: string}>, low_stock: list<array{id: int, title: string, quantity: float, min: float, unit: ?string}>, recent: list<array{id: int, title: string, type: string, movement: ?string, date: ?string}>, counts: array<string, int>}
     */
    public function overview(User $user): array
    {
        $assets = $this->access->query($user, 'assets')->where('status', '!=', 'archived')->orderBy('title')->get();
        $inService = $assets->whereNotIn('status', self::OUT_OF_SERVICE);
        $holders = $inService->map(fn (object $asset): int => (int) ($this->catalog->data($asset)['assignee_id'] ?? 0))->filter();
        $titles = $this->sales->titles($holders->unique()->values()->all());
        $today = today()->toDateString();
        $horizon = today()->addDays(30)->toDateString();
        $warranty = $inService->filter(fn (object $asset): bool => ($end = $this->catalog->data($asset)['warranty_end'] ?? null) && $end >= $today && $end <= $horizon)
            ->map(fn (object $asset): array => ['id' => $asset->id, 'title' => $asset->title, 'tag' => ($data = $this->catalog->data($asset))['asset_tag'] ?? null, 'warranty_end' => $data['warranty_end']])->sortBy('warranty_end')->values();
        $items = $this->access->can($user, 'inventory_items') ? $this->access->query($user, 'inventory_items')->where('status', 'active')->orderBy('title')->get() : collect();
        $low = $items->filter(fn (object $item): bool => $this->stockLevel($item)['low'])
            ->map(fn (object $item): array => ['id' => $item->id, 'title' => $item->title, 'unit' => $this->catalog->data($item)['unit'] ?? null] + $this->stockLevel($item))->values();
        $recent = collect(['asset_movements', 'stock_movements'])->filter(fn (string $type): bool => $this->access->can($user, $type))
            ->flatMap(fn (string $type) => $this->access->query($user, $type)->where('status', 'completed')->latest('updated_at')->limit(10)->get())
            ->sortByDesc('updated_at')->take(10)
            ->map(fn (object $row): array => ['id' => $row->id, 'title' => $row->title, 'type' => $row->type, 'movement' => ($data = $this->catalog->data($row))['movement'] ?? null, 'date' => $data['movement_date'] ?? null])->values();

        return [
            'by_status' => $assets->countBy('status')->all(),
            'by_category' => $assets->countBy(fn (object $asset): string => $this->catalog->data($asset)['category'] ?? 'other')->sortDesc()->all(),
            'custody' => $holders->countBy()->sortDesc()->map(fn (int $count, int $employee): array => ['employee' => $titles[$employee] ?? null, 'employee_id' => $employee, 'count' => $count])->values()->all(),
            'maintenance' => $assets->where('status', 'maintenance')->values()->all(),
            'warranty' => $warranty->all(),
            'low_stock' => $low->all(),
            'recent' => $recent->all(),
            'counts' => [
                'assets' => $inService->count(),
                'assigned' => $holders->count(),
                'available' => $inService->where('status', 'active')->filter(fn (object $asset): bool => empty($this->catalog->data($asset)['assignee_id']))->count(),
                'maintenance' => $assets->where('status', 'maintenance')->count(),
                'warranty' => $warranty->count(),
                'items' => $items->count(),
                'low_stock' => $low->count(),
            ],
        ];
    }

    /**
     * Context for an asset or inventory item page: custody and movement history, plus the movements that make sense next.
     *
     * @return array<string, mixed>|null
     */
    public function panel(User $user, object $record): ?array
    {
        if ($record->type === 'assets') {
            $data = $this->catalog->data($record);
            $holder = (int) ($data['assignee_id'] ?? 0);
            $canMove = $this->access->managesAssets($user) && $this->access->canCreate($user, 'asset_movements');
            $movements = $this->access->managesAssets($user) ? $this->movements($record->id) : collect();
            $people = $this->sales->titles($movements->flatMap(fn (object $row): array => array_values(array_filter([($move = $this->catalog->data($row))['to_employee_id'] ?? null, $move['from_employee_id'] ?? null])))->push($holder)->filter()->unique()->values()->all());
            $actions = match (true) {
                in_array($record->status, self::OUT_OF_SERVICE, true) => [],
                $record->status === 'maintenance' => ['maintenance_done'],
                (bool) $holder => ['transfer', 'return', 'maintenance'],
                default => ['assign', 'maintenance'],
            };
            $maintenanceCost = $movements->where('status', 'completed')->filter(fn (object $row): bool => (float) ($this->catalog->data($row)['cost'] ?? 0) > 0)
                ->groupBy(fn (object $row): string => strtoupper(trim((string) ($this->catalog->data($row)['currency'] ?? ''))) ?: 'USD')
                ->map(fn (Collection $rows): float => round($rows->sum(fn (object $row): float => (float) $this->catalog->data($row)['cost']), 2))->all();

            return ['type' => 'assets', 'holder' => $holder ? ($people[$holder] ?? null) : null, 'manager' => $this->access->managesAssets($user), 'actions' => $canMove ? $actions : [], 'movements' => $movements->map(fn (object $row): array => ['id' => $row->id, 'title' => $row->title, 'status' => $row->status, 'movement' => ($move = $this->catalog->data($row))['movement'] ?? null, 'date' => $move['movement_date'] ?? null, 'to' => $people[$move['to_employee_id'] ?? 0] ?? null, 'from' => $people[$move['from_employee_id'] ?? 0] ?? null, 'cost' => $move['cost'] ?? null, 'currency' => $move['currency'] ?? null])->all(), 'maintenance_cost' => $maintenanceCost];
        }
        if ($record->type === 'inventory_items') {
            $movements = DB::table('operating_records')->where('type', 'stock_movements')->where('status', '!=', 'archived')
                ->whereIn('id', DB::table('operating_links')->where('field', 'item_id')->where('target_id', $record->id)->select('record_id'))
                ->orderByDesc('id')->limit(20)->get();

            return ['type' => 'inventory_items', 'level' => $this->stockLevel($record), 'unit' => $this->catalog->data($record)['unit'] ?? null, 'canMove' => $record->status === 'active' && $this->access->canCreate($user, 'stock_movements'), 'movements' => $movements->map(fn (object $row): array => ['id' => $row->id, 'title' => $row->title, 'status' => $row->status, 'movement' => ($move = $this->catalog->data($row))['movement'] ?? null, 'quantity' => $move['quantity'] ?? null, 'after' => $move['quantity_after'] ?? null, 'date' => $move['movement_date'] ?? null])->all()];
        }

        return null;
    }

    /**
     * Daily safety net: low stock not yet announced, and one alert per warranty end date within 30 days.
     *
     * @return array{low_stock: int, warranty: int}
     */
    public function sendAlerts(CarbonImmutable $now): array
    {
        $sent = ['low_stock' => 0, 'warranty' => 0];
        foreach (DB::table('operating_records')->where('type', 'inventory_items')->whereIn('status', ['active', 'inactive'])->get() as $item) {
            $sent['low_stock'] += (int) $this->checkStock($item);
        }
        $today = $now->toDateString();
        $horizon = $now->addDays(30)->toDateString();
        $recipients = $this->notifier->roles(self::STOCK_RECIPIENTS)->all();
        foreach (DB::table('operating_records')->where('type', 'assets')->whereNotIn('status', self::OUT_OF_SERVICE)->where('data->warranty_end', '>=', $today)->where('data->warranty_end', '<=', $horizon)->get() as $asset) {
            $data = $this->catalog->data($asset);
            if (($data['warranty_alerted_for'] ?? null) === $data['warranty_end']) {
                continue;
            }
            $this->notifier->send([...$recipients, ! empty($data['primary_id']) ? $this->notifier->employeeUser((int) $data['primary_id']) : null], new OperatingAlert(
                'warranty_ending',
                ['ar' => 'ضمان أصل ينتهي قريبًا', 'en' => 'Asset warranty ending'],
                ['ar' => 'ضمان "'.$asset->title.'" '.($data['asset_tag'] ?? '').' ينتهي في '.$data['warranty_end'].'.', 'en' => 'The warranty of "'.$asset->title.'" '.($data['asset_tag'] ?? '').' ends on '.$data['warranty_end'].'.'],
                '/admin/operations/record/'.$asset->id,
            ));
            $this->workflow->mark($asset, 'warranty_alerted_for', $data['warranty_end'], 'Warranty ending alert sent');
            $sent['warranty']++;
        }

        return $sent;
    }

    public function notifyReceiver(object $movement): void
    {
        $data = $this->catalog->data($movement);
        if (! in_array($data['movement'] ?? null, ['assign', 'transfer'], true) || empty($data['to_employee_id'])) {
            return;
        }
        $asset = DB::table('operating_records')->find($data['asset_id']);
        $tag = $this->catalog->data($asset)['asset_tag'] ?? '';
        $this->notifier->send([$this->notifier->employeeUser((int) $data['to_employee_id'])], new OperatingAlert(
            'asset_assigned',
            ['ar' => 'عهدة جديدة باسمك', 'en' => 'New asset in your custody'],
            ['ar' => 'تم تسليمك "'.$asset->title.'" '.$tag.'. راجع حالته وأبلغ عن أي ملاحظة.', 'en' => 'You received "'.$asset->title.'" '.$tag.'. Check its condition and report any issue.'],
            '/admin/operations/record/'.$asset->id,
        ));
    }

    /**
     * @return Collection<int, object>
     */
    public function movements(int $assetId): Collection
    {
        return DB::table('operating_records')->where('type', 'asset_movements')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'asset_id')->where('target_id', $assetId)->select('record_id'))
            ->orderByDesc('id')->get();
    }

    public function nextTag(): string
    {
        $last = DB::table('operating_records')->where('type', 'assets')->where('data->asset_tag', 'like', 'AST-%')->get()
            ->map(fn (object $asset): int => (int) substr((string) ($this->catalog->data($asset)['asset_tag'] ?? ''), 4))->max() ?? 0;

        return 'AST-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Case- and space-insensitive lookup among records still in use, so a tag or serial number identifies one item.
     */
    public function isTaken(string $type, string $key, string $value, ?int $exceptId = null): bool
    {
        $needle = mb_strtolower(trim($value));

        return DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))->get()
            ->contains(fn (object $record): bool => mb_strtolower(trim((string) ($this->catalog->data($record)[$key] ?? ''))) === $needle);
    }
}
