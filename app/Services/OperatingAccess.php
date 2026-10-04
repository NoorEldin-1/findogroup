<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperatingAccess
{
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
        if ($type === 'onboarding' && $user->role !== 'hr') {
            return false;
        }
        if ($action === 'read' && (($user->role === 'finance' && in_array($type, ['projects', 'clients'], true)) || ($user->role === 'sales' && in_array($type, ['projects', 'scopes'], true)))) {
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
            if (in_array($user->role, ['designer', 'video_editor'], true) && $type !== ($user->role === 'designer' ? 'creative_assets' : 'videos')) {
                return false;
            }
        }

        return in_array($user->role, config('operating.groups.'.$entity['group'].'.'.$action, []), true);
    }

    public function delegation(User $user, string $type): ?object
    {
        return DB::table('operating_records')->where('type', 'delegations')->where('status', 'active')->where('data->user_id', $user->id)->where('data->entity_type', $type)->where('data->action', 'approve')->where('data->starts_at', '<=', today()->toDateString())->where('data->expires_at', '>=', today()->toDateString())->first();
    }

    public function query(User $user, string $type): Builder
    {
        abort_unless($this->can($user, $type), 403);
        $query = DB::table('operating_records')->where('type', $type);
        if (in_array($user->role, ['owner', 'hr'], true)) {
            return $query;
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
        if (($entity['assigned'] ?? false) && ! in_array($user->role, ['delivery', 'tech_lead', 'marketing', 'sales', 'finance'], true)) {
            return $query->where(function (Builder $scope) use ($user, $employeeId): void {
                $scope->where('owner_id', $user->id);
                if ($employeeId) {
                    foreach (['assignee_id', 'reviewer_id', 'tester_id'] as $field) {
                        $scope->orWhere('data->'.$field, $employeeId);
                    }
                }
            });
        }
        if (in_array($user->role, ['developer', 'qa'], true) && $type === 'projects') {
            $taskIds = $this->query($user, 'tasks')->pluck('id');
            $projects = DB::table('operating_links')->whereIn('record_id', $taskIds)->where('field', 'project_id')->pluck('target_id');

            return $query->whereIn('id', $projects);
        }

        return $query;
    }

    public function record(User $user, int $id, string $action = 'read'): object
    {
        $record = DB::table('operating_records')->find($id);
        abort_unless($record, 404);
        abort_unless($this->can($user, $record->type, $action) && $this->query($user, $record->type)->where('id', $id)->exists(), 403);

        return $record;
    }

    public function visibleData(User $user, object $record): array
    {
        $data = $this->catalog->data($record);
        if ($record->type === 'employees' && ! in_array($user->role, ['owner', 'hr'], true) && $this->employeeId($user) !== $record->id) {
            return array_intersect_key($data, array_flip(['job_title', 'department', 'manager_id']));
        }
        if ($record->type === 'projects' && $user->role === 'sales') {
            return array_intersect_key($data, array_flip(['client_id', 'outcome', 'original_due', 'approved_due', 'client_contact']));
        }

        return $data;
    }

    public function restrictedContext(User $user, object $record): bool
    {
        return ($record->type === 'projects' && $user->role === 'sales') || ($record->type === 'employees' && ! in_array($user->role, ['owner', 'hr'], true) && $this->employeeId($user) !== $record->id);
    }

    public function fields(User $user, string $type, ?object $record = null): array
    {
        $fields = $this->catalog->entity($type)['fields'];
        if ($type === 'employees' && ! in_array($user->role, ['owner', 'hr'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['job_title', 'department', 'manager_id'], true));
        }
        if ($type === 'projects' && $user->role === 'sales') {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['client_id', 'outcome', 'original_due', 'approved_due', 'client_contact'], true));
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
