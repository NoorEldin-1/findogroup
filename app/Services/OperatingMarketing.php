<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingMarketing
{
    public const QUALIFIED_STAGES = ['qualified', 'discovery', 'proposal', 'negotiation', 'won'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingSales $sales) {}

    /**
     * Results for one campaign. Leads and deals are aggregates over every linked lead, including leads the viewer cannot open,
     * so marketing sees its outcomes without reading the sales pipeline. Revenue is booked won value in the campaign currency.
     * A period limits metrics by metric date and leads by creation date (a cohort view).
     *
     * @return array{currency: string, channel: ?string, budget_cap: float, spend: float, budget_used: ?float, impressions: int, clicks: int, ctr: ?float, leads: int, reported_leads: int, qualified: int, won: int, lost: int, revenue: float, reported_revenue: float, other_currency_won: int, cpl: ?float, conversion: ?float, roi: ?float}
     */
    public function stats(object $campaign, ?string $from = null, ?string $to = null): array
    {
        $data = $this->catalog->data($campaign);
        $currency = $this->currency($data);
        $metrics = $this->linked('campaign_metrics', $campaign->id)->filter(fn (object $row): bool => $this->inPeriod($this->catalog->data($row)['metric_date'] ?? null, $from, $to));
        $sum = fn (string $key): float => (float) $metrics->sum(fn (object $row): float => (float) ($this->catalog->data($row)[$key] ?? 0));
        $spend = $metrics->isNotEmpty() || $from || $to ? $sum('spent') : (float) ($data['spent'] ?? 0);
        $leads = $this->linked('opportunities', $campaign->id)->filter(fn (object $row): bool => $this->inPeriod(substr((string) $row->created_at, 0, 10), $from, $to));
        $won = $leads->where('status', 'won');
        $sameCurrency = $won->filter(fn (object $row): bool => $this->currency($this->catalog->data($row)) === $currency);
        $revenue = (float) $sameCurrency->sum(fn (object $row): float => (float) ($this->catalog->data($row)['expected_value'] ?? 0));
        $impressions = (int) $sum('impressions');
        $clicks = (int) $sum('clicks');
        $count = $leads->count();
        $cap = (float) ($data['budget_cap'] ?? 0);

        return [
            'currency' => $currency,
            'channel' => $data['channel'] ?? null,
            'budget_cap' => $cap,
            'spend' => round($spend, 2),
            'budget_used' => $cap > 0 ? round($spend / $cap * 100, 1) : null,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : null,
            'leads' => $count,
            'reported_leads' => (int) $sum('leads'),
            'qualified' => $leads->whereIn('status', self::QUALIFIED_STAGES)->count(),
            'won' => $won->count(),
            'lost' => $leads->where('status', 'lost')->count(),
            'revenue' => round($revenue, 2),
            'reported_revenue' => round($sum('revenue'), 2),
            'other_currency_won' => $won->count() - $sameCurrency->count(),
            'cpl' => $count ? round($spend / $count, 2) : null,
            'conversion' => $count ? round($won->count() / $count * 100, 1) : null,
            'roi' => $spend > 0 ? round(($revenue - $spend) / $spend * 100, 1) : null,
        ];
    }

    /**
     * @return array{stats: array<string, mixed>, tasks: list<array{id: int, title: string, status: string, assignee: ?string, due: ?string, overdue: bool}>, task_counts: array{open: int, done: int, overdue: int}}
     */
    public function campaign(User $user, object $campaign): array
    {
        $tasks = $this->access->can($user, 'team_tasks') ? $this->access->query($user, 'team_tasks')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'campaign_id')->where('target_id', $campaign->id)->select('record_id'))->get() : collect();
        $titles = $this->sales->titles($tasks->map(fn (object $task): mixed => $this->catalog->data($task)['assignee_id'] ?? null)->filter()->unique()->values()->all());
        $today = today()->toDateString();
        $rows = $tasks->map(function (object $task) use ($titles, $today): array {
            $data = $this->catalog->data($task);

            return ['id' => $task->id, 'title' => $task->title, 'status' => $task->status, 'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null, 'due' => $data['due_date'] ?? null, 'overdue' => $task->status !== 'done' && ($data['due_date'] ?? '9999') < $today];
        })->sortBy('due')->values();

        return ['stats' => $this->stats($campaign), 'tasks' => $rows->all(), 'task_counts' => ['open' => $rows->where('status', '!=', 'done')->count(), 'done' => $rows->where('status', 'done')->count(), 'overdue' => $rows->where('overdue', true)->count()]];
    }

    /**
     * Campaigns with spend, leads or a running state in the period; totals and channel groups are per currency, never converted.
     * Lead sources count every lead created in the period, as an aggregate.
     *
     * @param  array{from?: ?string, to?: ?string, channel?: ?string}  $filters
     * @return array{from: string, to: string, rows: list<array<string, mixed>>, totals: list<array<string, mixed>>, channels: list<array<string, mixed>>, sources: list<array<string, mixed>>, best: ?array<string, mixed>, worst: ?array<string, mixed>}
     */
    public function performance(User $user, array $filters = []): array
    {
        $from = ($filters['from'] ?? null) ?: today()->startOfMonth()->toDateString();
        $to = ($filters['to'] ?? null) ?: today()->toDateString();
        $rows = collect();
        foreach ($this->access->query($user, 'campaigns')->where('status', '!=', 'archived')->when($filters['channel'] ?? null, fn ($query, $channel) => $query->where('data->channel', $channel))->orderBy('title')->get() as $campaign) {
            $stats = $this->stats($campaign, $from, $to);
            if ($stats['spend'] > 0 || $stats['leads'] || $stats['reported_leads'] || $campaign->status === 'running') {
                $rows->push(['id' => $campaign->id, 'title' => $campaign->title, 'status' => $campaign->status] + $stats);
            }
        }
        $channels = $rows->groupBy(fn (array $row): string => ($row['channel'] ?? 'unspecified').'|'.$row['currency'])
            ->map(fn (Collection $group, string $key): array => array_combine(['channel', 'currency'], explode('|', $key)) + $this->aggregate($group))
            ->sortByDesc('spend')->values();
        $ranked = $channels->filter(fn (array $channel): bool => $channel['roi'] !== null)->sortBy('roi')->values();
        $sources = DB::table('operating_records')->where('type', 'opportunities')->where('status', '!=', 'archived')->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->get()
            ->groupBy(fn (object $lead): string => $this->catalog->data($lead)['lead_source'] ?? 'unspecified')
            ->map(function (Collection $group, string $source): array {
                $won = $group->where('status', 'won');
                $value = [];
                foreach ($won as $lead) {
                    $data = $this->catalog->data($lead);
                    $currency = $this->currency($data);
                    $value[$currency] = round(($value[$currency] ?? 0) + (float) ($data['expected_value'] ?? 0), 2);
                }

                return ['source' => $source, 'leads' => $group->count(), 'won' => $won->count(), 'conversion' => round($won->count() / $group->count() * 100, 1), 'won_value' => $value];
            })->sortByDesc('leads')->values();

        return [
            'from' => $from,
            'to' => $to,
            'rows' => $rows->all(),
            'totals' => $rows->groupBy('currency')->map(fn (Collection $group, string $currency): array => ['currency' => $currency] + $this->aggregate($group))->values()->all(),
            'channels' => $channels->all(),
            'sources' => $sources->all(),
            'best' => $ranked->count() > 1 ? $ranked->last() : null,
            'worst' => $ranked->count() > 1 ? $ranked->first() : null,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{campaigns: int, spend: float, impressions: int, clicks: int, leads: int, won: int, revenue: float, cpl: ?float, conversion: ?float, roi: ?float}
     */
    private function aggregate(Collection $rows): array
    {
        $spend = round((float) $rows->sum('spend'), 2);
        $leads = (int) $rows->sum('leads');
        $won = (int) $rows->sum('won');
        $revenue = round((float) $rows->sum('revenue'), 2);

        return [
            'campaigns' => $rows->count(),
            'spend' => $spend,
            'impressions' => (int) $rows->sum('impressions'),
            'clicks' => (int) $rows->sum('clicks'),
            'leads' => $leads,
            'won' => $won,
            'revenue' => $revenue,
            'cpl' => $leads ? round($spend / $leads, 2) : null,
            'conversion' => $leads ? round($won / $leads * 100, 1) : null,
            'roi' => $spend > 0 ? round(($revenue - $spend) / $spend * 100, 1) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function currency(array $data): string
    {
        return strtoupper(trim((string) ($data['currency'] ?? ''))) ?: 'USD';
    }

    /**
     * @return Collection<int, object>
     */
    private function linked(string $type, int $campaignId): Collection
    {
        return DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'campaign_id')->where('target_id', $campaignId)->select('record_id'))->get();
    }

    private function inPeriod(?string $date, ?string $from, ?string $to): bool
    {
        if (! $from && ! $to) {
            return true;
        }

        return $date !== null && $date !== '' && (! $from || $date >= $from) && (! $to || $date <= $to);
    }
}
