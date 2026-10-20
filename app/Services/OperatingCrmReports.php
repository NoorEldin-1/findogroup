<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class OperatingCrmReports
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingCrm $crm, private OperatingSales $sales, private OperatingLeads $leadRows) {}

    /**
     * @return Collection<int, object>
     */
    public function leads(User $user, string $scope): Collection
    {
        return $this->narrow($this->access->query($user, 'opportunities')->where('status', '!=', 'archived'), $user, $scope)->orderBy('id')->get();
    }

    public function activityQuery(User $user, string $scope): Builder
    {
        return $this->narrow($this->access->query($user, 'activities')->where('status', '!=', 'archived'), $user, $scope);
    }

    /**
     * Managers see the team unless they ask for their own; everyone else is limited to records they own or are assigned.
     */
    private function narrow(Builder $query, User $user, string $scope): Builder
    {
        if ($scope === 'team' && $this->crm->isManager($user)) {
            return $query;
        }
        $employee = $this->access->employeeId($user);

        return $query->where(fn (Builder $mine) => $mine->where('owner_id', $user->id)->when($employee, fn (Builder $assigned) => $assigned->orWhere('data->assignee_id', $employee)));
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user, string $from, string $to, string $scope): array
    {
        $leads = $this->leads($user, $scope);
        $created = $leads->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $reach = $this->crm->stageReach($created->pluck('id')->all());
        $contacted = $meetings = $closedFromMeeting = 0;
        foreach ($created as $lead) {
            $stages = $reach[$lead->id] ?? [$lead->status];
            $contacted += (int) $this->crm->reached($stages, 'contacted');
            if ($this->crm->reached($stages, 'meeting')) {
                $meetings++;
                $closedFromMeeting += (int) ($lead->status === 'won');
            }
        }
        $won = $leads->filter(fn (object $lead): bool => $lead->status === 'won' && $this->closedBetween($this->catalog->data($lead), $from, $to));
        $revenue = $potential = [];
        foreach ($won as $lead) {
            $this->addValue($revenue, $this->catalog->data($lead));
        }
        foreach ($leads->whereIn('status', OperatingSales::OPEN_STAGES) as $lead) {
            $this->addValue($potential, $this->catalog->data($lead));
        }
        $alerts = $leads->filter(fn (object $lead): bool => $this->crm->openAlert($lead));
        $canActivities = $this->access->can($user, 'activities');
        $employee = (int) ($this->access->employeeId($user) ?? 0);

        return [
            'kpis' => [
                'total_leads' => $created->count(),
                'activities' => $canActivities ? $this->activityQuery($user, $scope)->where('status', 'done')->where('data->completed_at', '>=', $from)->where('data->completed_at', '<=', $to.'T23:59')->count() : 0,
                'won' => $won->count(), 'revenue' => $revenue, 'potential' => $potential, 'sla_alerts' => $alerts->count(),
                'contact_to_meeting' => $this->percent($meetings, $contacted), 'meeting_to_close' => $this->percent($closedFromMeeting, $meetings),
            ],
            'recent' => $this->leadRows->rows($leads->sortByDesc('created_at')->take(6)),
            'breached' => $this->leadRows->rows($alerts->sortBy(fn (object $lead): string => $this->catalog->data($lead)['sla_due_at'] ?? '')->take(6)),
            'today' => $canActivities ? $this->access->query($user, 'activities')->where('status', 'planned')->where('data->assignee_id', $employee)->where('data->due_at', '<=', today()->format('Y-m-d').'T23:59')->get()->sortBy(fn (object $row): string => $this->catalog->data($row)['due_at'] ?? '')->values() : collect(),
            'mine' => $canActivities ? $this->access->query($user, 'activities')->where('status', 'done')->where('data->assignee_id', $employee)->latest('updated_at')->limit(6)->get() : collect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function calendar(User $user, string $month, string $scope = 'team', ?int $assignee = null): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $gridStart = $start->subDays(($start->dayOfWeek + 1) % 7);
        $end = $start->endOfMonth()->startOfDay();
        $gridEnd = $end->addDays((12 - $end->dayOfWeek) % 7);
        $items = [];
        if ($this->access->can($user, 'activities')) {
            $now = now()->format('Y-m-d\TH:i');
            $rows = $this->activityQuery($user, $scope)->whereIn('status', ['planned', 'done'])->where('data->due_at', '>=', $gridStart->format('Y-m-d'))->where('data->due_at', '<=', $gridEnd->format('Y-m-d').'T23:59')->when($assignee, fn (Builder $query) => $query->where('data->assignee_id', $assignee))->get();
            foreach ($rows as $row) {
                $data = $this->catalog->data($row);
                $items[substr($data['due_at'], 0, 10)][] = ['id' => $row->id, 'title' => $row->title, 'time' => substr($data['due_at'], 11, 5), 'kind' => $data['kind'] ?? null, 'status' => $row->status, 'overdue' => $row->status === 'planned' && $data['due_at'] < $now, 'lead' => isset($data['opportunity_id']) ? (int) $data['opportunity_id'] : null];
            }
        }
        $weeks = [];
        $today = today()->format('Y-m-d');
        for ($day = $gridStart, $index = 0; $day <= $gridEnd; $day = $day->addDay(), $index++) {
            $date = $day->format('Y-m-d');
            $list = $items[$date] ?? [];
            usort($list, fn (array $a, array $b): int => strcmp($a['time'], $b['time']));
            $weeks[intdiv($index, 7)][] = ['date' => $date, 'day' => (int) $day->format('j'), 'inMonth' => $day->month === $start->month, 'today' => $date === $today, 'items' => $list];
        }

        return ['month' => $month, 'label' => $start->locale(app()->getLocale())->translatedFormat('F Y'), 'prev' => $start->subMonth()->format('Y-m'), 'next' => $start->addMonth()->format('Y-m'), 'weeks' => $weeks];
    }

    public function between(object $lead, string $from, string $to): bool
    {
        $at = substr((string) $lead->created_at, 0, 10);

        return $at >= $from && $at <= $to;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function closedBetween(array $data, string $from, string $to): bool
    {
        $at = substr((string) ($data['closed_at'] ?? ''), 0, 10);

        return $at !== '' && $at >= $from && $at <= $to;
    }

    public function percent(int $part, int $whole): ?float
    {
        return $whole ? round($part / $whole * 100, 1) : null;
    }

    /**
     * @param  array<string, float>  $sums
     * @param  array<string, mixed>  $data
     */
    private function addValue(array &$sums, array $data): void
    {
        $currency = $this->sales->currency($data);
        $sums[$currency] = ($sums[$currency] ?? 0) + (float) ($data['expected_value'] ?? 0);
    }
}
