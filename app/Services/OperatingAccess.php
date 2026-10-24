<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperatingAccess
{
    /**
     * Entity types a plain sales rep sees only when they created or are assigned to the record.
     */
    public const SALES_SCOPED = ['opportunities', 'activities'];

    /**
     * Delivery child records a developer or QA sees only for projects they can already see.
     */
    public const PROJECT_SCOPED = ['project_members', 'sprints', 'deliverables'];

    /**
     * Roles that run procurement end to end; other procurement readers only browse suppliers and file their own purchase requests.
     */
    public const PROCUREMENT_MANAGERS = ['owner', 'finance'];

    /**
     * Supplier fields kept to the procurement managers.
     */
    public const SUPPLIER_PRIVATE_FIELDS = ['bank_details', 'tax_number'];

    /**
     * What an employee sees of an asset in their custody: identification and condition, no cost or account details.
     */
    public const ASSET_CUSTODY_FIELDS = ['category', 'kind', 'asset_tag', 'serial_number', 'brand_model', 'condition', 'location', 'assignee_id'];

    public function __construct(private OperatingCatalog $catalog) {}

    public function employeeId(User $user): ?int
    {
        return DB::table('operating_records')->where('type', 'employees')->where('data->user_id', $user->id)->value('id');
    }

    public function can(User $user, string $type, string $action = 'read'): bool
    {
        if (! $user->active) {
            return false;
        }
        $entity = $this->catalog->entity($type);
        if ($user->role === 'owner') {
            return true;
        }
        if (in_array($type, ['attendance', 'holidays'], true) && $action !== 'read') {
            return $user->role === 'hr';
        }
        if ($type === 'attendance' && $this->employeeId($user)) {
            return true;
        }
        if ($type === 'assets' && $action === 'read' && ! $this->managesAssets($user) && $this->employeeId($user)) {
            return true;
        }
        if ($type === 'onboarding' && $user->role !== 'hr') {
            return false;
        }
        if ($action === 'read' && (($user->role === 'finance' && in_array($type, ['projects', 'clients'], true)) || (in_array($user->role, ['sales', 'sales_manager'], true) && in_array($type, ['projects', 'scopes'], true)))) {
            return true;
        }
        if (in_array($type, ['delegations', 'exceptions', 'policies'], true) && $action !== 'read') {
            return false;
        }
        if ($type === 'contracts' && $user->role === 'data_entry') {
            return false;
        }
        if ($user->role === 'data_entry' && ! in_array($type, ['data_batches', 'employees', 'goals', 'evaluations', 'leave', 'improvement_plans'], true)) {
            return false;
        }
        if ($entity['group'] === 'procurement' && ! in_array($user->role, self::PROCUREMENT_MANAGERS, true)) {
            if (! in_array($user->role, config('operating.groups.procurement.read', []), true)) {
                return false;
            }

            return match ($action) {
                'read' => in_array($type, ['suppliers', 'purchase_requests'], true),
                'write' => $type === 'purchase_requests',
                default => $this->delegation($user, $type) !== null,
            };
        }
        if ($action === 'approve') {
            if (in_array($user->role, $entity['approve_roles'] ?? [], true)) {
                return true;
            }

            return $this->delegation($user, $type) !== null;
        }
        if ($action === 'write') {
            if ($type === 'evaluations') {
                return true;
            }
            if (in_array($type, ['goals', 'leave', 'improvement_plans'], true) && $this->employeeId($user)) {
                return true;
            }
            if ($user->role === 'developer' && ! in_array($type, ['tasks', 'time_entries', 'reviews'], true)) {
                return false;
            }
            if ($user->role === 'qa' && ! in_array($type, ['test_cases', 'defects', 'releases'], true)) {
                return false;
            }
            if (in_array($user->role, ['designer', 'video_editor'], true)) {
                return $type === 'team_tasks' || in_array($user->role, config('operating.groups.'.$entity['group'].'.write', []), true) && $type === ($user->role === 'designer' ? 'creative_assets' : 'videos');
            }
        }

        return in_array($user->role, config('operating.groups.'.$entity['group'].'.'.$action, []), true);
    }

    /**
     * Creating is narrower than editing for work queues: only leads open new development or marketing tasks.
     */
    public function canCreate(User $user, string $type): bool
    {
        if (! $this->can($user, $type, 'write')) {
            return false;
        }

        return match ($type) {
            'tasks' => in_array($user->role, ['owner', 'delivery', 'tech_lead'], true),
            'team_tasks' => in_array($user->role, ['owner', 'marketing', 'media_buyer'], true),
            default => true,
        };
    }

    /**
     * Asset managers run the register; every other employee only sees the assets in their own custody.
     */
    public function managesAssets(User $user): bool
    {
        return in_array($user->role, config('operating.groups.assets.read', []), true);
    }

    public function delegation(User $user, string $type): ?object
    {
        return DB::table('operating_records')->where('type', 'delegations')->where('status', 'active')->where('data->user_id', $user->id)->where('data->entity_type', $type)->where('data->action', 'approve')->where('data->starts_at', '<=', today()->toDateString())->where('data->expires_at', '>=', today()->toDateString())->first();
    }

    /**
     * Records of a type the user may see; a lead shared with a sales rep is included for reading only.
     */
    public function query(User $user, string $type, string $action = 'read'): Builder
    {
        abort_unless($this->can($user, $type), 403);
        $query = DB::table('operating_records')->where('type', $type);
        if ($type === 'purchase_requests' && ! in_array($user->role, self::PROCUREMENT_MANAGERS, true)) {
            $employeeId = $this->employeeId($user);

            return $query->where(fn (Builder $scope) => $scope->where('owner_id', $user->id)->when($employeeId, fn (Builder $mine) => $mine->orWhere('data->requester_id', $employeeId)));
        }
        if (in_array($user->role, ['owner', 'hr'], true)) {
            return $query;
        }
        if ($type === 'assets' && ! $this->managesAssets($user)) {
            return $query->where('data->assignee_id', $this->employeeId($user) ?? 0);
        }
        $entity = $this->catalog->entity($type);
        $employeeId = $this->employeeId($user);
        if ($entity['private'] ?? false) {
            return $query->where(function (Builder $scope) use ($user, $employeeId): void {
                $scope->where('owner_id', $user->id);
                if ($employeeId) {
                    $scope->orWhere('data->assignee_id', $employeeId);
                }
                if ($user->role === 'delivery' && $employeeId) {
                    $reports = DB::table('operating_records')->where('type', 'employees')->where('data->manager_id', $employeeId)->pluck('id');
                    $scope->orWhereIn('data->assignee_id', $reports);
                }
            });
        }
        if ($type === 'employees') {
            return $query;
        }
        $salesScoped = $user->role === 'sales' && in_array($type, self::SALES_SCOPED, true);
        if ($salesScoped || (($entity['assigned'] ?? false) && ! in_array($user->role, ['delivery', 'tech_lead', 'marketing', 'sales', 'sales_manager', 'finance'], true))) {
            return $query->where(function (Builder $scope) use ($user, $employeeId, $type, $action): void {
                $scope->where('owner_id', $user->id);
                if ($employeeId) {
                    foreach (['assignee_id', 'reviewer_id', 'tester_id'] as $field) {
                        $scope->orWhere('data->'.$field, $employeeId);
                    }
                }
                if ($type === 'opportunities' && $action === 'read') {
                    $scope->orWhereIn('id', DB::table('crm_lead_relations')->where('user_id', $user->id)->where('relation', OperatingLeadRelations::SHARED)->select('lead_id'));
                }
            });
        }
        if ($user->role === 'sales' && $type === 'quotes') {
            $quoteIds = DB::table('operating_links')->where('field', 'opportunity_id')->whereIn('target_id', $this->query($user, 'opportunities', 'write')->pluck('id'))->pluck('record_id');

            return $query->where(fn (Builder $scope) => $scope->where('owner_id', $user->id)->orWhereIn('id', $quoteIds));
        }
        if (in_array($user->role, ['developer', 'qa'], true) && $type === 'projects') {
            $taskIds = $this->query($user, 'tasks')->pluck('id');
            $memberIds = $employeeId ? DB::table('operating_records')->where('type', 'project_members')->where('status', 'active')->where('data->assignee_id', $employeeId)->pluck('id') : collect();
            $projects = DB::table('operating_links')->whereIn('record_id', $taskIds->merge($memberIds))->where('field', 'project_id')->pluck('target_id');

            return $query->whereIn('id', $projects);
        }
        if (in_array($user->role, ['developer', 'qa'], true) && in_array($type, self::PROJECT_SCOPED, true)) {
            $members = DB::table('operating_links')->where('field', 'project_id')->whereIn('target_id', $this->query($user, 'projects')->pluck('id'))->pluck('record_id');

            return $query->whereIn('id', $members);
        }

        return $query;
    }

    public function record(User $user, int $id, string $action = 'read'): object
    {
        $record = DB::table('operating_records')->find($id);
        abort_unless($record, 404);
        abort_unless($this->can($user, $record->type, $action) && $this->query($user, $record->type, $action)->where('id', $id)->exists(), 403);

        return $record;
    }

    public function visibleData(User $user, object $record): array
    {
        $data = $this->catalog->data($record);
        if ($record->type === 'employees' && ! in_array($user->role, ['owner', 'hr'], true) && $this->employeeId($user) !== $record->id) {
            return array_intersect_key($data, array_flip(['job_title', 'department', 'manager_id']));
        }
        if ($record->type === 'projects' && in_array($user->role, ['sales', 'sales_manager'], true)) {
            return array_intersect_key($data, array_flip(['client_id', 'outcome', 'original_due', 'approved_due', 'client_contact']));
        }
        if ($record->type === 'suppliers' && ! in_array($user->role, self::PROCUREMENT_MANAGERS, true)) {
            return array_diff_key($data, array_flip(self::SUPPLIER_PRIVATE_FIELDS));
        }
        if ($record->type === 'assets' && ! $this->managesAssets($user)) {
            return array_intersect_key($data, array_flip(self::ASSET_CUSTODY_FIELDS));
        }

        return $data;
    }

    public function restrictedContext(User $user, object $record): bool
    {
        return ($record->type === 'assets' && ! $this->managesAssets($user)) || ($record->type === 'projects' && in_array($user->role, ['sales', 'sales_manager'], true)) || ($record->type === 'employees' && ! in_array($user->role, ['owner', 'hr'], true) && $this->employeeId($user) !== $record->id);
    }

    public function fields(User $user, string $type, ?object $record = null): array
    {
        $fields = $this->catalog->entity($type)['fields'];
        if ($type === 'employees' && ! in_array($user->role, ['owner', 'hr'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['job_title', 'department', 'manager_id'], true));
        }
        if ($type === 'projects' && in_array($user->role, ['sales', 'sales_manager'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['client_id', 'outcome', 'original_due', 'approved_due', 'client_contact'], true));
        }
        if ($type === 'suppliers' && ! in_array($user->role, self::PROCUREMENT_MANAGERS, true)) {
            $fields = array_filter($fields, fn (array $field): bool => ! in_array($field['key'], self::SUPPLIER_PRIVATE_FIELDS, true));
        }
        if ($type === 'assets' && ! $this->managesAssets($user)) {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], self::ASSET_CUSTODY_FIELDS, true));
        }
        if ($type === 'team_tasks' && ! in_array($user->role, ['owner', 'marketing', 'media_buyer'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['deliverable_url', 'notes'], true));
        }
        if ($type === 'goals' && ! in_array($user->role, ['owner', 'hr', 'delivery'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => $field['key'] === 'evidence');
        }
        if ($type === 'improvement_plans' && ! in_array($user->role, ['owner', 'hr', 'delivery'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => $field['key'] === 'employee_comment');
        }
        if ($type === 'evaluations' && ! in_array($user->role, ['owner', 'hr', 'delivery'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['self_review', 'employee_comment'], true));
        }

        return array_values($fields);
    }
}
