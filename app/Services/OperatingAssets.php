<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
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

    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow, private OperatingNotifier $notifier) {}

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
