<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuditController extends Controller
{
    /**
     * Record-less events a sales manager may see: their own CRM configuration, exports and imports.
     */
    private const MANAGER_SYSTEM_ACTIONS = ['configure_crm', 'export_leads', 'import_leads'];

    /**
     * Engine bookkeeping columns that are not business changes.
     */
    private const IGNORED = ['id', 'uuid', 'type', 'owner_id', 'created_by', 'version', 'created_at', 'updated_at', 'source', 'external_id', 'synced_at'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->role, OperatingCrm::MANAGERS, true), 403);
        $filters = $request->validate(['action' => 'nullable|string|max:40', 'type' => 'nullable|string|max:60', 'user' => 'nullable|integer', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'q' => 'nullable|string|max:200']);
        $query = DB::table('operating_events as e')->leftJoin('operating_records as r', 'r.id', '=', 'e.record_id')->leftJoin('users as u', 'u.id', '=', 'e.user_id')->select('e.*', 'r.title as record_title', 'r.type as record_type', 'u.name as user_name');
        if ($user->role !== 'owner') {
            $query->where(fn (Builder $scope) => $scope->whereIn('r.type', OperatingCrm::AUDITED_TYPES)->orWhere(fn (Builder $system) => $system->whereNull('e.record_id')->whereIn('e.action', self::MANAGER_SYSTEM_ACTIONS)));
        }
        $query->when($filters['action'] ?? null, fn (Builder $scope, string $action) => $scope->where('e.action', $action))
            ->when($filters['type'] ?? null, fn (Builder $scope, string $type) => $scope->where('r.type', $type))
            ->when($filters['user'] ?? null, fn (Builder $scope, int $id) => $scope->where('e.user_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $scope, string $from) => $scope->where('e.created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn (Builder $scope, string $to) => $scope->where('e.created_at', '<=', $to.' 23:59:59'))
            ->when($filters['q'] ?? null, fn (Builder $scope, string $search) => $scope->where(fn (Builder $text) => $text->where('r.title', 'like', '%'.$search.'%')->orWhere('e.reason', 'like', '%'.$search.'%')));
        $page = $query->orderByDesc('e.id')->paginate(50)->withQueryString();
        $events = collect($page->items())->map(fn (object $event): object => tap($event, fn (object $row) => $row->changes = $this->changes($row)));
        $types = $user->role === 'owner' ? array_keys($this->catalog->entities()) : OperatingCrm::AUDITED_TYPES;

        return response()->view('admin.operating.audit-log', [
            'page' => $page, 'events' => $events->groupBy(fn (object $event): string => substr((string) $event->created_at, 0, 10)), 'filters' => $filters,
            'types' => collect($types)->mapWithKeys(fn (string $type): array => [$type => $this->catalog->label($this->catalog->entity($type)['label'])])->all(),
            'actions' => DB::table('operating_events')->distinct()->orderBy('action')->pluck('action')->all(),
            'users' => DB::table('users')->orderBy('name')->pluck('name', 'id')->all(),
            'collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access,
        ]);
    }

    /**
     * @return list<array{field: string, before: mixed, after: mixed}>
     */
    private function changes(object $event): array
    {
        $flatten = function (?string $json): array {
            $side = json_decode($json ?? 'null', true) ?: [];
            $data = is_string($side['data'] ?? null) ? (json_decode($side['data'], true) ?: []) : (is_array($side['data'] ?? null) ? $side['data'] : []);

            return array_intersect_key($side, array_flip(['status', 'title'])) + $data + array_diff_key($side, array_flip([...self::IGNORED, 'status', 'title', 'data']));
        };
        $before = $flatten($event->before);
        $after = $flatten($event->after);
        $changes = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) != ($after[$key] ?? null)) {
                $changes[] = ['field' => (string) $key, 'before' => $before[$key] ?? null, 'after' => $after[$key] ?? null];
            }
        }

        return array_slice($changes, 0, 30);
    }
}
