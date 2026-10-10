<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingSprints
{
    public const PRIORITY_RANK = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

    public const BOARD_COLUMNS = ['backlog', 'ready', 'in_progress', 'code_review', 'qa', 'ready_to_release', 'done'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * Completion by task count; remaining effort uses remaining_hours, or the estimate when nobody updated it yet.
     * "At risk" means completion trails elapsed time by more than 20 points.
     *
     * @return array{percent: ?int, tasks: array{done: int, total: int}, hours: array{estimate: float, remaining: float}, days: array{total: int, elapsed: int, left: int}, time_percent: int, overdue_tasks: int, late: bool, at_risk: bool}
     */
    public function progress(object $sprint): array
    {
        $data = $this->catalog->data($sprint);
        $tasks = $this->tasks($sprint);
        $open = $tasks->reject(fn (object $task): bool => $task->status === 'done');
        $hours = fn (object $task, string $key): float => (float) ($this->catalog->data($task)[$key] ?? 0);
        $remaining = fn (object $task): float => ($this->catalog->data($task)['remaining_hours'] ?? null) !== null ? $hours($task, 'remaining_hours') : $hours($task, 'estimate_hours');
        $today = today()->toDateString();
        $start = CarbonImmutable::parse($data['start_date']);
        $total = (int) $start->diffInDays(CarbonImmutable::parse($data['end_date'])) + 1;
        $elapsed = $today < $data['start_date'] ? 0 : ($today > $data['end_date'] ? $total : (int) $start->diffInDays(today()) + 1);
        $percent = $tasks->count() ? (int) round(($tasks->count() - $open->count()) / $tasks->count() * 100) : null;
        $timePercent = (int) round($elapsed / max(1, $total) * 100);

        return [
            'percent' => $percent,
            'tasks' => ['done' => $tasks->count() - $open->count(), 'total' => $tasks->count()],
            'hours' => ['estimate' => round($tasks->sum(fn (object $task): float => $hours($task, 'estimate_hours')), 1), 'remaining' => round($open->sum($remaining), 1)],
            'days' => ['total' => $total, 'elapsed' => $elapsed, 'left' => max(0, $total - $elapsed)],
            'time_percent' => $timePercent,
            'overdue_tasks' => $open->filter(fn (object $task): bool => ($due = $this->due($task)) !== null && $due < $today)->count(),
            'late' => $sprint->status === 'active' && $today > $data['end_date'],
            'at_risk' => $sprint->status === 'active' && $percent !== null && $percent + 20 < $timePercent,
        ];
    }

    /**
     * Cards are limited to tasks the viewer can open; ordering is priority, then due date.
     *
     * @return array<string, mixed>
     */
    public function board(User $user, object $project): array
    {
        $tasks = $this->access->query($user, 'tasks')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'project_id')->where('target_id', $project->id)->select('record_id'))->get();
        $sprints = $this->access->query($user, 'sprints')->where('status', '!=', 'archived')->where('data->project_id', $project->id)->orderBy('id')->get();
        $titles = $this->sales->titles($tasks->flatMap(fn (object $task): array => array_values(array_filter([($data = $this->catalog->data($task))['assignee_id'] ?? null, $data['tester_id'] ?? null])))->unique()->values()->all());
        $cards = $tasks->map(fn (object $task): array => $this->card($task, $titles))
            ->sort(fn (array $a, array $b): int => [self::PRIORITY_RANK[$a['priority']] ?? 4, $a['due'] ?? '9999', $a['id']] <=> [self::PRIORITY_RANK[$b['priority']] ?? 4, $b['due'] ?? '9999', $b['id']])->values();
        $inSprint = fn (int $sprintId): Collection => $cards->where('sprint_id', $sprintId)->values();
        $active = $sprints->firstWhere('status', 'active');

        return [
            'active' => $active ? ['sprint' => $active, 'progress' => $this->progress($active), 'columns' => collect(self::BOARD_COLUMNS)->mapWithKeys(fn (string $state): array => [$state => $inSprint($active->id)->where('status', $state)->values()->all()])->all()] : null,
            'planned' => $sprints->where('status', 'planned')->map(fn (object $sprint): array => ['sprint' => $sprint, 'progress' => $this->progress($sprint), 'cards' => $inSprint($sprint->id)->all()])->values()->all(),
            'completed' => $sprints->where('status', 'completed')->sortByDesc('id')->take(5)->map(fn (object $sprint): array => ['sprint' => $sprint, 'summary' => $this->catalog->data($sprint)['completed_summary'] ?? null])->values()->all(),
            'backlog' => $cards->filter(fn (array $card): bool => ! $card['sprint_id'] && ! $card['done'])->values()->all(),
            'overdue' => $cards->where('overdue', true)->sortBy('due')->values()->all(),
            'in_sprints' => $cards->filter(fn (array $card): bool => $card['sprint_id'] && ! $card['done'] && $card['source'] === 'findo')->values()->all(),
            'targets' => $sprints->whereIn('status', ['planned', 'active'])->values()->all(),
        ];
    }

    /**
     * Move tasks between the backlog (null sprint) and a planned or active sprint through the normal task save, so every gate and audit event still applies.
     *
     * @param  list<int|string>  $taskIds
     */
    public function plan(User $user, object $project, ?int $sprintId, array $taskIds, string $reason): int
    {
        abort_unless(in_array($user->role, ['owner', 'delivery', 'tech_lead'], true), 403);
        if ($sprintId) {
            $sprint = $this->access->record($user, $sprintId);
            abort_unless($sprint->type === 'sprints' && (int) ($this->catalog->data($sprint)['project_id'] ?? 0) === $project->id, 404);
            if (! in_array($sprint->status, ['planned', 'active'], true)) {
                $this->workflow->fail('أضف المهام إلى سبرنت مخطط أو جارٍ فقط.', 'Add tasks to a planned or active sprint only.');
            }
        }

        return DB::transaction(function () use ($user, $project, $sprintId, $taskIds, $reason): int {
            $moved = 0;
            foreach (array_unique(array_map('intval', $taskIds)) as $taskId) {
                $task = $this->access->record($user, $taskId, 'write');
                $data = $this->catalog->data($task);
                abort_unless($task->type === 'tasks' && (int) ($data['project_id'] ?? 0) === $project->id, 404);
                if ($task->source !== 'findo' || in_array($task->status, ['done', 'archived'], true)) {
                    $this->workflow->fail('مهام Jira والمهام المنجزة لا تُنقل بين السبرنتات.', 'Jira and finished tasks cannot move between sprints.');
                }
                if ((int) ($data['sprint_id'] ?? 0) === (int) $sprintId) {
                    continue;
                }
                $this->sales->saveAs($user, 'tasks', ['title' => $task->title, 'version' => $task->version, 'reason' => $reason, 'data' => array_merge($data, ['sprint_id' => $sprintId])], $task->id);
                $moved++;
            }

            return $moved;
        });
    }

    /**
     * Every non-archived task planned in the sprint, regardless of who is viewing (progress is an aggregate).
     *
     * @return Collection<int, object>
     */
    public function tasks(object $sprint): Collection
    {
        return DB::table('operating_records')->where('type', 'tasks')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'sprint_id')->where('target_id', $sprint->id)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * Runs inside the sprint transition transaction: unfinished work goes back to the backlog with an audited reason, done work stays in the sprint history.
     *
     * @return array{done: int, carried: int}
     */
    public function releaseUnfinished(object $sprint, User $user, string $reason): array
    {
        $summary = ['done' => 0, 'carried' => 0];
        foreach ($this->tasks($sprint) as $task) {
            if ($task->status === 'done') {
                $summary['done']++;

                continue;
            }
            $summary['carried']++;
            $data = array_merge($this->catalog->data($task), ['sprint_id' => null, 'carried_from_sprint' => $sprint->id]);
            DB::table('operating_records')->where('id', $task->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $task->version + 1, 'updated_at' => now()]);
            DB::table('operating_links')->where('record_id', $task->id)->where('field', 'sprint_id')->delete();
            $this->workflow->event(DB::table('operating_records')->find($task->id), 'update', 'Returned to backlog when sprint "'.$sprint->title.'" completed: '.$reason, ['sprint_id' => $sprint->id], ['sprint_id' => null], $user->id);
        }

        return $summary;
    }

    /**
     * @param  array<int, string>  $titles
     * @return array<string, mixed>
     */
    private function card(object $task, array $titles): array
    {
        $data = $this->catalog->data($task);
        $done = $task->source === 'jira' ? in_array(strtolower((string) ($data['source_status'] ?? '')), OperatingProjects::DONE_JIRA_STATUSES, true) : $task->status === 'done';
        $due = $this->due($task);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'source' => $task->source,
            'done' => $done,
            'sprint_id' => empty($data['sprint_id']) ? null : (int) $data['sprint_id'],
            'carried' => empty($data['sprint_id']) && ! empty($data['carried_from_sprint']),
            'priority' => $data['priority'] ?? null,
            'assignee' => $titles[$data['assignee_id'] ?? 0] ?? ($data['assignee_name'] ?? null),
            'tester' => $titles[$data['tester_id'] ?? 0] ?? null,
            'estimate' => isset($data['estimate_hours']) ? (float) $data['estimate_hours'] : null,
            'remaining' => isset($data['remaining_hours']) ? (float) $data['remaining_hours'] : null,
            'due' => $due,
            'overdue' => ! $done && $due !== null && $due < today()->toDateString(),
        ];
    }

    private function due(object $task): ?string
    {
        $data = $this->catalog->data($task);

        return ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
    }
}
