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

    /**
     * @return array<string, mixed>
     */
    public function funnel(User $user, string $from, string $to, string $scope): array
    {
        $leads = $this->leads($user, $scope);
        $created = $leads->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $reach = $this->crm->stageReach($created->pluck('id')->all());
        $reached = fn (object $lead, string $stage): bool => $this->crm->reached($reach[$lead->id] ?? [$lead->status], $stage);

        $stages = [];
        $previous = null;
        foreach (OperatingCrm::FUNNEL as $stage) {
            $count = $created->filter(fn (object $lead): bool => $reached($lead, $stage))->count();
            $stages[] = ['stage' => $stage, 'label' => $this->catalog->status($stage), 'count' => $count, 'rate' => $previous === null ? null : $this->percent($count, $previous), 'share' => $this->percent($count, $created->count())];
            $previous = $count;
        }

        $hours = $created->map(fn (object $lead): ?float => $this->crm->responseHours($lead))->filter(fn (?float $value): bool => $value !== null);
        $won = $leads->filter(fn (object $lead): bool => $lead->status === 'won' && $this->closedBetween($this->catalog->data($lead), $from, $to));
        $wonValue = $openValue = [];
        foreach ($won as $lead) {
            $this->addValue($wonValue, $this->catalog->data($lead));
        }
        $open = $leads->whereIn('status', OperatingSales::OPEN_STAGES);
        foreach ($open as $lead) {
            $this->addValue($openValue, $this->catalog->data($lead));
        }

        $start = CarbonImmutable::parse($from);
        $daily = $start->diffInDays(CarbonImmutable::parse($to)) <= 62;
        $trend = [];
        for ($day = $start; $day->format('Y-m-d') <= $to; $day = $daily ? $day->addDay() : $day->addMonth()->startOfMonth()) {
            $trend[$day->format($daily ? 'Y-m-d' : 'Y-m')] = 0;
        }
        foreach ($created as $lead) {
            $trend[substr((string) $lead->created_at, 0, $daily ? 10 : 7)]++;
        }

        $months = [];
        $lastMonth = CarbonImmutable::parse($to)->startOfMonth();
        for ($i = 11; $i >= 0; $i--) {
            $months[$lastMonth->subMonths($i)->format('Y-m')] = ['count' => 0, 'value' => []];
        }
        foreach ($leads->where('status', 'won') as $lead) {
            $data = $this->catalog->data($lead);
            $key = substr((string) ($data['closed_at'] ?? ''), 0, 7);
            if (isset($months[$key])) {
                $months[$key]['count']++;
                $this->addValue($months[$key]['value'], $data);
            }
        }

        $campaignTitles = $this->sales->titles($created->map(fn (object $lead): mixed => $this->catalog->data($lead)['campaign_id'] ?? null)->filter()->unique()->values()->all());
        $campaigns = [];
        foreach ($created as $lead) {
            $data = $this->catalog->data($lead);
            $name = $campaignTitles[$data['campaign_id'] ?? 0] ?? __('بدون حملة');
            $campaigns[$name] ??= ['name' => $name, 'leads' => 0, 'contacted' => 0, 'meetings' => 0, 'won' => 0, 'revenue' => [], 'conversion' => null];
            $campaigns[$name]['leads']++;
            $campaigns[$name]['contacted'] += (int) $reached($lead, 'contacted');
            $campaigns[$name]['meetings'] += (int) $reached($lead, 'meeting');
            if ($lead->status === 'won') {
                $campaigns[$name]['won']++;
                $this->addValue($campaigns[$name]['revenue'], $data);
            }
        }
        foreach ($campaigns as &$campaign) {
            $campaign['conversion'] = $this->percent($campaign['won'], $campaign['leads']);
        }
        unset($campaign);

        return [
            'total' => $created->count(), 'won' => $won->count(), 'won_value' => $wonValue,
            'potential' => ['count' => $open->count(), 'value' => $openValue],
            'avg_response' => $hours->isNotEmpty() ? round($hours->avg(), 1) : null,
            'stages' => $stages, 'trend' => $trend, 'months' => $months,
            'campaigns' => collect($campaigns)->sortByDesc('leads')->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function team(User $user, string $from, string $to): array
    {
        abort_unless($this->crm->isManager($user), 403);
        $created = $this->leads($user, 'team')->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $names = $this->sales->titles($created->map(fn (object $lead): mixed => $this->catalog->data($lead)['assignee_id'] ?? null)->filter()->unique()->values()->all());
        $agents = [];
        $stages = [];
        $quality = ['hot' => 0, 'warm' => 0, 'cold' => 0, 'bad' => 0, 'unknown' => 0];
        foreach ($created as $lead) {
            $data = $this->catalog->data($lead);
            $key = (int) ($data['assignee_id'] ?? 0);
            $agents[$key] ??= ['name' => $names[$key] ?? __('غير مسند'), 'leads' => 0, 'open' => 0, 'won' => 0, 'lost' => 0, 'win_rate' => null, 'revenue' => [], 'sla_breaches' => 0, 'response' => [], 'avg_response' => null, 'activities' => 0];
            $agents[$key]['leads']++;
            $agents[$key]['open'] += (int) in_array($lead->status, OperatingSales::OPEN_STAGES, true);
            if (in_array($lead->status, ['won', 'lost'], true)) {
                $agents[$key][$lead->status]++;
            }
            if ($lead->status === 'won') {
                $this->addValue($agents[$key]['revenue'], $data);
            }
            $agents[$key]['sla_breaches'] += (int) in_array($this->crm->slaState($lead), ['breached', 'late'], true);
            if (($hours = $this->crm->responseHours($lead)) !== null) {
                $agents[$key]['response'][] = $hours;
            }
            $label = $this->catalog->status($lead->status);
            $stages[$label] = ($stages[$label] ?? 0) + 1;
            $quality[$data['lead_quality'] ?? 'unknown'] = ($quality[$data['lead_quality'] ?? 'unknown'] ?? 0) + 1;
        }
        if ($this->access->can($user, 'activities')) {
            foreach ($this->activityQuery($user, 'team')->where('status', 'done')->where('data->completed_at', '>=', $from)->where('data->completed_at', '<=', $to.'T23:59')->get() as $activity) {
                $key = (int) ($this->catalog->data($activity)['assignee_id'] ?? 0);
                if (isset($agents[$key])) {
                    $agents[$key]['activities']++;
                }
            }
        }
        foreach ($agents as &$agent) {
            $agent['win_rate'] = $this->percent($agent['won'], $agent['won'] + $agent['lost']);
            $agent['avg_response'] = $agent['response'] ? round(array_sum($agent['response']) / count($agent['response']), 1) : null;
            unset($agent['response']);
        }
        unset($agent);
        $agents = collect($agents)->sortByDesc('leads')->values()->all();

        return ['agents' => $agents, 'stages' => $stages, 'quality' => $quality, 'max' => max([1, ...array_column($agents, 'leads')])];
    }

    /**
     * @return array<string, mixed>
     */
    public function sla(User $user, string $from, string $to, string $scope, ?int $assignee = null): array
    {
        $leads = $this->leads($user, $scope)->when($assignee, fn (Collection $all) => $all->filter(fn (object $lead): bool => (int) ($this->catalog->data($lead)['assignee_id'] ?? 0) === $assignee));
        $created = $leads->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $names = $this->sales->titles($leads->map(fn (object $lead): mixed => $this->catalog->data($lead)['assignee_id'] ?? null)->filter()->unique()->values()->all());
        $states = ['pending' => 0, 'met' => 0, 'late' => 0, 'breached' => 0];
        $byDay = [];
        $agents = [];
        $hours = [];
        foreach ($created as $lead) {
            $state = $this->crm->slaState($lead);
            if (! $state) {
                continue;
            }
            $states[$state]++;
            $day = substr((string) $lead->created_at, 0, 10);
            $byDay[$day][$state] = ($byDay[$day][$state] ?? 0) + 1;
            $key = (int) ($this->catalog->data($lead)['assignee_id'] ?? 0);
            $agents[$key] ??= ['name' => $names[$key] ?? __('غير مسند'), 'total' => 0, 'met' => 0, 'late' => 0, 'breached' => 0, 'compliance' => null, 'response' => [], 'avg_response' => null];
            $agents[$key]['total']++;
            if ($state !== 'pending') {
                $agents[$key][$state]++;
            }
            if (($response = $this->crm->responseHours($lead)) !== null) {
                $hours[] = $response;
                $agents[$key]['response'][] = $response;
            }
        }
        foreach ($agents as &$agent) {
            $agent['compliance'] = $this->percent($agent['met'], $agent['met'] + $agent['late'] + $agent['breached']);
            $agent['avg_response'] = $agent['response'] ? round(array_sum($agent['response']) / count($agent['response']), 1) : null;
            unset($agent['response']);
        }
        unset($agent);
        $trend = [];
        for ($day = CarbonImmutable::parse($from); $day->format('Y-m-d') <= $to; $day = $day->addDay()) {
            $counts = $byDay[$day->format('Y-m-d')] ?? [];
            $trend[$day->format('Y-m-d')] = $this->percent($counts['met'] ?? 0, ($counts['met'] ?? 0) + ($counts['late'] ?? 0) + ($counts['breached'] ?? 0));
        }
        $kinds = $results = [];
        $activityCount = 0;
        if ($this->access->can($user, 'activities')) {
            $done = $this->activityQuery($user, $scope)->where('status', 'done')->where('data->completed_at', '>=', $from)->where('data->completed_at', '<=', $to.'T23:59')->when($assignee, fn (Builder $query) => $query->where('data->assignee_id', $assignee))->orderBy('id')->get();
            $activityCount = $done->count();
            foreach ($done as $activity) {
                $data = $this->catalog->data($activity);
                $kinds[$data['kind'] ?? 'other'] = ($kinds[$data['kind'] ?? 'other'] ?? 0) + 1;
                if (! empty($data['result'])) {
                    $results[$data['result']] = ($results[$data['result']] ?? 0) + 1;
                }
            }
        }
        $now = CarbonImmutable::now();
        $breached = $leads->filter(fn (object $lead): bool => $this->crm->openAlert($lead))->map(function (object $lead) use ($names, $now): array {
            $data = $this->catalog->data($lead);

            return ['id' => $lead->id, 'title' => $lead->title, 'assignee' => $names[$data['assignee_id'] ?? 0] ?? null, 'due' => str_replace('T', ' ', $data['sla_due_at']), 'hours' => round(CarbonImmutable::parse($data['sla_due_at'])->diffInMinutes($now) / 60, 1)];
        })->sortByDesc('hours')->take(50)->values()->all();

        return [
            'states' => $states, 'compliance' => $this->percent($states['met'], $states['met'] + $states['late'] + $states['breached']),
            'avg_response' => $hours ? round(array_sum($hours) / count($hours), 1) : null, 'activities' => $activityCount,
            'trend' => $trend, 'kinds' => $kinds, 'results' => $results,
            'agents' => collect($agents)->sortByDesc('total')->values()->all(), 'breached' => $breached,
        ];
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
