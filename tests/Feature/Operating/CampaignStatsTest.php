<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMarketing;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CampaignStatsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function campaign(array $data = []): int
    {
        return $this->operatingRecord('campaigns', 'running', array_merge(['channel' => 'facebook', 'objective' => 'leads', 'budget_cap' => 1000, 'currency' => 'USD', 'spent' => 999], $data), 'Ramadan ERP');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function metric(int $campaign, string $date, array $data): int
    {
        return $this->operatingRecord('campaign_metrics', 'active', array_merge(['campaign_id' => $campaign, 'metric_date' => $date], $data), 'Day');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function lead(int $campaign, string $status, array $data = []): int
    {
        return $this->operatingRecord('opportunities', $status, array_merge(['campaign_id' => $campaign, 'lead_company' => 'Co', 'lead_source' => 'facebook'], $data), 'Lead '.$status);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(int $campaign, ?string $from = null, ?string $to = null): array
    {
        return app(OperatingMarketing::class)->stats(DB::table('operating_records')->find($campaign), $from, $to);
    }

    public function test_stats_combine_reported_metrics_with_crm_leads_and_deals(): void
    {
        $campaign = $this->campaign();
        $this->metric($campaign, '2026-10-11', ['spent' => 200, 'impressions' => 10000, 'clicks' => 100, 'leads' => 6, 'revenue' => 900]);
        $this->metric($campaign, '2026-10-12', ['spent' => 300, 'impressions' => 10000, 'clicks' => 300, 'leads' => 4]);
        $this->lead($campaign, 'new');
        $this->lead($campaign, 'qualified');
        $this->lead($campaign, 'won', ['expected_value' => 2000, 'currency' => 'USD']);
        $this->lead($campaign, 'won', ['expected_value' => 500, 'currency' => 'EGP']);
        $this->lead($campaign, 'lost');

        $stats = $this->stats($campaign);

        $this->assertSame(500.0, $stats['spend']);
        $this->assertSame(50.0, $stats['budget_used']);
        $this->assertSame(2.0, $stats['ctr']);
        $this->assertSame(5, $stats['leads']);
        $this->assertSame(10, $stats['reported_leads']);
        $this->assertSame(3, $stats['qualified']);
        $this->assertSame(2, $stats['won']);
        $this->assertSame(2000.0, $stats['revenue']);
        $this->assertSame(1, $stats['other_currency_won']);
        $this->assertSame(900.0, $stats['reported_revenue']);
        $this->assertSame(100.0, $stats['cpl']);
        $this->assertSame(40.0, $stats['conversion']);
        $this->assertSame(300.0, $stats['roi']);
    }

    public function test_without_metrics_the_campaign_spend_is_used_and_empty_ratios_stay_null(): void
    {
        $stats = $this->stats($this->campaign(['spent' => 0]));

        $this->assertSame(0.0, $stats['spend']);
        $this->assertNull($stats['cpl']);
        $this->assertNull($stats['conversion']);
        $this->assertNull($stats['roi']);
        $this->assertNull($stats['ctr']);
        $this->assertSame(250.0, $this->stats($this->campaign(['spent' => 250]))['spend']);
    }

    public function test_a_period_filters_metrics_by_date_and_leads_by_creation(): void
    {
        $campaign = $this->campaign();
        $this->metric($campaign, '2026-09-30', ['spent' => 100]);
        $this->metric($campaign, '2026-10-05', ['spent' => 40]);
        $old = $this->lead($campaign, 'new');
        DB::table('operating_records')->where('id', $old)->update(['created_at' => '2026-09-20 10:00:00']);
        $this->lead($campaign, 'new');

        $stats = $this->stats($campaign, '2026-10-01', '2026-10-31');
        $this->assertSame(40.0, $stats['spend']);
        $this->assertSame(1, $stats['leads']);
    }

    public function test_the_campaign_page_shows_results_and_team_tasks(): void
    {
        $campaign = $this->campaign();
        $this->operatingRecord('team_tasks', 'in_progress', ['campaign_id' => $campaign, 'assignee_id' => $this->employeeFor($this->operatingUser('designer')), 'due_date' => '2026-10-10', 'description' => 'Slides'], 'Carousel design');

        $this->actingAs($this->operatingUser('marketing'))->get("/admin/operations/record/{$campaign}")->assertOk()->assertSee(__('نتائج الحملة'))->assertSee('Carousel design');
        $this->actingAs($this->operatingUser('developer'))->get("/admin/operations/record/{$campaign}")->assertForbidden();
    }
}
