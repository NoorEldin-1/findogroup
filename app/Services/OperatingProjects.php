<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperatingProjects
{
    public const DONE_JIRA_STATUSES = ['done', 'closed', 'resolved'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingSales $sales) {}

    /**
     * Completion is an aggregate, so it counts every linked task even when the viewer cannot open each one.
     * Tasks drive the percentage; without tasks, accepted milestones do; a closed project is complete.
     *
     * @return array{percent: ?int, basis: ?string, tasks: array{done: int, total: int}, milestones: array{accepted: int, total: int}, due: ?string, overdue: bool, due_soon: bool}
     */
    public function progress(object $project): array
    {
        $data = $this->catalog->data($project);
        $children = fn (string $type) => DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')->whereIn('id', DB::table('operating_links')->where('field', 'project_id')->where('target_id', $project->id)->select('record_id'))->get();
        $tasks = $children('tasks');
        $done = $tasks->filter(fn (object $task): bool => $task->source === 'jira' ? in_array(strtolower((string) ($this->catalog->data($task)['source_status'] ?? '')), self::DONE_JIRA_STATUSES, true) : $task->status === 'done')->count();
        $milestones = $children('milestones');
        $accepted = $milestones->where('status', 'accepted')->count();
        [$percent, $basis] = match (true) {
            $project->status === 'closed' => [100, 'closed'],
            $tasks->isNotEmpty() => [(int) round($done / $tasks->count() * 100), 'tasks'],
            $milestones->isNotEmpty() => [(int) round($accepted / $milestones->count() * 100), 'milestones'],
            default => [null, null],
        };
        $due = ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
        $open = ! in_array($project->status, ['closed', 'archived'], true);
        $today = today()->toDateString();

        return [
            'percent' => $percent,
            'basis' => $basis,
            'tasks' => ['done' => $done, 'total' => $tasks->count()],
            'milestones' => ['accepted' => $accepted, 'total' => $milestones->count()],
            'due' => $due,
            'overdue' => $open && $due && $due < $today,
            'due_soon' => $open && $due && $due >= $today && $due <= today()->addDays(14)->toDateString(),
        ];
    }

    /**
     * Filters on fields the viewer cannot read are ignored, so they cannot be used to infer hidden values.
     * Task-based completion is shown only to viewers who can read tasks; others get the schedule flags alone.
     *
     * @param  array{status?: string|null, priority?: string|null, type?: string|null}  $filters
     * @return list<array{id: int, title: string, status: string, client: ?string, manager: ?string, type: ?string, priority: ?string, members: int, progress: array<string, mixed>}>
     */
    public function portfolio(User $user, array $filters = []): array
    {
        $readable = array_column($this->access->fields($user, 'projects'), 'key');
        $showProgress = $this->access->can($user, 'tasks');
        $projects = $this->access->query($user, 'projects')->where('status', '!=', 'archived')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when(in_array('priority', $readable, true) ? ($filters['priority'] ?? null) : null, fn ($query, $priority) => $query->where('data->priority', $priority))
            ->when(in_array('project_type', $readable, true) ? ($filters['type'] ?? null) : null, fn ($query, $type) => $query->where('data->project_type', $type))
            ->get();
        $visible = $projects->mapWithKeys(fn (object $project): array => [$project->id => $this->access->visibleData($user, $project)]);
        $titles = $this->sales->titles($visible->flatMap(fn (array $data): array => array_filter([$data['client_id'] ?? null, $data['manager_id'] ?? null]))->unique()->values()->all());
        $members = DB::table('operating_links')->where('field', 'project_id')->whereIn('target_id', $projects->pluck('id'))->whereIn('record_id', DB::table('operating_records')->where('type', 'project_members')->where('status', 'active')->select('id'))->selectRaw('target_id, count(*) as total')->groupBy('target_id')->pluck('total', 'target_id');
        $rank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        $rows = $projects->map(function (object $project) use ($visible, $titles, $members, $showProgress): array {
            $data = $visible[$project->id];

            return ['id' => $project->id, 'title' => $project->title, 'status' => $project->status, 'client' => $titles[$data['client_id'] ?? 0] ?? null, 'manager' => $titles[$data['manager_id'] ?? 0] ?? null, 'type' => $data['project_type'] ?? null, 'priority' => $data['priority'] ?? null, 'members' => (int) ($members[$project->id] ?? 0), 'progress' => $showProgress ? $this->progress($project) : $this->schedule($project, $data)];
        })->all();
        usort($rows, fn (array $a, array $b): int => [$rank[$a['priority']] ?? 4, $a['progress']['due'] ?? '9999'] <=> [$rank[$b['priority']] ?? 4, $b['progress']['due'] ?? '9999']);

        return $rows;
    }

    /**
     * Deadline flags only, from fields the viewer can already read.
     *
     * @param  array<string, mixed>  $data
     * @return array{percent: null, basis: null, tasks: array{done: int, total: int}, milestones: array{accepted: int, total: int}, due: ?string, overdue: bool, due_soon: bool}
     */
    private function schedule(object $project, array $data): array
    {
        $due = ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
        $open = ! in_array($project->status, ['closed', 'archived'], true);
        $today = today()->toDateString();

        return ['percent' => null, 'basis' => null, 'tasks' => ['done' => 0, 'total' => 0], 'milestones' => ['accepted' => 0, 'total' => 0], 'due' => $due, 'overdue' => $open && $due && $due < $today, 'due_soon' => $open && $due && $due >= $today && $due <= today()->addDays(14)->toDateString()];
    }
}
