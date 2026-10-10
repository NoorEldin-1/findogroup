<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingMilestones
{
    /**
     * States in which a passed due date is the team's delay; a submitted milestone is waiting on the client.
     */
    public const OPEN = ['planned', 'in_progress'];

    public const NOTICE_DAYS = 3;

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingNotifier $notifier, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * @return Collection<int, object>
     */
    public function deliverables(int $milestoneId): Collection
    {
        return DB::table('operating_records')->where('type', 'deliverables')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'milestone_id')->where('target_id', $milestoneId)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * @return array{percent: ?int, delivered: int, total: int, delayed: int, due: ?string, overdue: bool, due_soon: bool}
     */
    public function progress(object $milestone): array
    {
        $data = $this->catalog->data($milestone);
        $deliverables = $this->deliverables($milestone->id);
        $delivered = $deliverables->where('status', 'delivered')->count();
        $today = today()->toDateString();
        $due = ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
        $open = in_array($milestone->status, self::OPEN, true);

        return [
            'percent' => $milestone->status === 'accepted' ? 100 : ($deliverables->count() ? (int) round($delivered / $deliverables->count() * 100) : null),
            'delivered' => $delivered,
            'total' => $deliverables->count(),
            'delayed' => $deliverables->filter(fn (object $item): bool => $item->status !== 'delivered' && ($this->catalog->data($item)['due_date'] ?? '9999') < $today)->count(),
            'due' => $due,
            'overdue' => $open && $due !== null && $due < $today,
            'due_soon' => $open && $due !== null && $due >= $today && $due <= today()->addDays(7)->toDateString(),
        ];
    }

    /**
     * @param  array{project?: int|string|null, owner?: int|string|null}  $filters
     * @return array<string, mixed>
     */
    public function tracker(User $user, array $filters = []): array
    {
        $scope = fn ($query) => $query
            ->when($filters['project'] ?? null, fn ($scoped, $project) => $scoped->where('data->project_id', (int) $project))
            ->when($filters['owner'] ?? null, fn ($scoped, $owner) => $scoped->where('data->assignee_id', (int) $owner));
        $milestones = $scope($this->access->query($user, 'milestones')->whereIn('status', ['planned', 'in_progress', 'submitted', 'accepted']))->get();
        $deliverables = $this->access->can($user, 'deliverables') ? $scope($this->access->query($user, 'deliverables')->whereIn('status', ['planned', 'in_progress'])->where('data->due_date', '<', today()->toDateString()))->get() : collect();
        $titles = $this->sales->titles($milestones->merge($deliverables)->flatMap(fn (object $row): array => array_values(array_filter([($data = $this->catalog->data($row))['project_id'] ?? null, $data['assignee_id'] ?? null, $data['milestone_id'] ?? null])))->unique()->values()->all());
        $board = ['overdue' => [], 'due_soon' => [], 'upcoming' => [], 'submitted' => [], 'accepted' => []];
        $recent = today()->subDays(30)->toDateString();
        foreach ($milestones as $milestone) {
            $data = $this->catalog->data($milestone);
            $progress = $this->progress($milestone);
            $bucket = match (true) {
                $milestone->status === 'accepted' => (($data['accepted_at'] ?? null) ?: substr((string) $milestone->updated_at, 0, 10)) >= $recent ? 'accepted' : null,
                $milestone->status === 'submitted' => 'submitted',
                $progress['overdue'] => 'overdue',
                $progress['due_soon'] => 'due_soon',
                default => 'upcoming',
            };
            if ($bucket) {
                $board[$bucket][] = ['id' => $milestone->id, 'title' => $milestone->title, 'project' => $titles[$data['project_id'] ?? 0] ?? null, 'project_id' => $data['project_id'] ?? null, 'owner' => $titles[$data['assignee_id'] ?? 0] ?? null, 'status' => $milestone->status, 'progress' => $progress];
            }
        }
        foreach ($board as $bucket => $rows) {
            usort($rows, fn (array $a, array $b): int => ($a['progress']['due'] ?? '9999') <=> ($b['progress']['due'] ?? '9999'));
            $board[$bucket] = $rows;
        }
        $board['delayed_deliverables'] = $deliverables->map(fn (object $item): array => ['id' => $item->id, 'title' => $item->title, 'milestone' => $titles[($data = $this->catalog->data($item))['milestone_id'] ?? 0] ?? null, 'project' => $titles[$data['project_id'] ?? 0] ?? null, 'owner' => $titles[$data['assignee_id'] ?? 0] ?? null, 'due' => $data['due_date']])->sortBy('due')->values()->all();
        $board['counts'] = array_map('count', $board);

        return $board;
    }

    /**
     * Daily: one alert when an open milestone enters the notice window, one when it passes its date, and one per overdue deliverable.
     * Markers store the due date that was announced, so an approved date change re-arms the alert.
     *
     * @return array{upcoming: int, overdue: int, deliverables: int}
     */
    public function sendReminders(CarbonImmutable $now): array
    {
        $sent = ['upcoming' => 0, 'overdue' => 0, 'deliverables' => 0];
        $today = $now->toDateString();
        $horizon = $now->addDays(self::NOTICE_DAYS)->toDateString();
        foreach (DB::table('operating_records')->where('type', 'milestones')->whereIn('status', self::OPEN)->get() as $milestone) {
            $data = $this->catalog->data($milestone);
            $due = ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
            if (! $due || $due > $horizon) {
                continue;
            }
            $overdue = $due < $today;
            $marker = $overdue ? 'overdue_alerted_for' : 'upcoming_alerted_for';
            if (($data[$marker] ?? null) === $due) {
                continue;
            }
            $this->notifier->send($this->recipients($data['project_id'] ?? null, $data['assignee_id'] ?? null), new OperatingAlert(
                $overdue ? 'milestone_overdue' : 'milestone_due_soon',
                $overdue ? ['ar' => 'محطة متأخرة', 'en' => 'Overdue milestone'] : ['ar' => 'محطة تقترب من موعدها', 'en' => 'Milestone due soon'],
                $overdue ? ['ar' => 'المحطة "'.$milestone->title.'" تجاوزت موعدها '.$due.'.', 'en' => 'The milestone "'.$milestone->title.'" passed its due date '.$due.'.'] : ['ar' => 'موعد المحطة "'.$milestone->title.'" هو '.$due.'.', 'en' => 'The milestone "'.$milestone->title.'" is due on '.$due.'.'],
                '/admin/operations/record/'.$milestone->id,
                $overdue,
            ));
            $this->workflow->mark($milestone, $marker, $due, $overdue ? 'Overdue milestone alert sent' : 'Upcoming milestone alert sent');
            $sent[$overdue ? 'overdue' : 'upcoming']++;
        }
        foreach (DB::table('operating_records')->where('type', 'deliverables')->whereIn('status', ['planned', 'in_progress'])->where('data->due_date', '<', $today)->get() as $deliverable) {
            $data = $this->catalog->data($deliverable);
            if (($data['overdue_alerted_for'] ?? null) === $data['due_date']) {
                continue;
            }
            $this->notifier->send($this->recipients($data['project_id'] ?? null, $data['assignee_id'] ?? null), new OperatingAlert(
                'deliverable_overdue',
                ['ar' => 'مخرج متأخر', 'en' => 'Overdue deliverable'],
                ['ar' => 'المخرج "'.$deliverable->title.'" تجاوز موعده '.$data['due_date'].'.', 'en' => 'The deliverable "'.$deliverable->title.'" passed its due date '.$data['due_date'].'.'],
                '/admin/operations/record/'.$deliverable->id,
            ));
            $this->workflow->mark($deliverable, 'overdue_alerted_for', $data['due_date'], 'Overdue deliverable alert sent');
            $sent['deliverables']++;
        }

        return $sent;
    }

    /**
     * The project's manager and the item's owner; a project without a manager alerts every active delivery manager.
     *
     * @return list<User|null>
     */
    private function recipients(int|string|null $projectId, int|string|null $ownerId): array
    {
        $project = $projectId ? DB::table('operating_records')->find($projectId) : null;
        $managerId = $project ? (int) ($this->catalog->data($project)['manager_id'] ?? 0) : 0;
        $managers = $managerId ? [$this->notifier->employeeUser($managerId)] : $this->notifier->roles(['delivery'])->all();

        return [...$managers, $ownerId ? $this->notifier->employeeUser((int) $ownerId) : null];
    }
}
