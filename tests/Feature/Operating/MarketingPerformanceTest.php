<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMarketing;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MarketingPerformanceTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    }

    private function campaign(string $title, string $channel, float $spend, int $leads, int $won, float $value, string $currency = 'USD'): int
    {
        $id = $this->operatingRecord('campaigns', 'running', ['channel' => $channel, 'objective' => 'leads', 'budget_cap' => 5000, 'currency' => $currency], $title);
        $this->operatingRecord('campaign_metrics', 'active', ['campaign_id' => $id, 'metric_date' => '2026-10-05', 'spent' => $spend], 'Day');
        for ($i = 0; $i < $leads; $i++) {
            $this->operatingRecord('opportunities', $i < $won ? 'won' : 'new', ['campaign_id' => $id, 'lead_company' => 'Co', 'lead_source' => $channel, 'expected_value' => $i < $won ? $value : null, 'currency' => $currency], 'Lead');
        }

        return $id;
    }

    public function test_channels_are_compared_and_ranked_by_roi(): void
    {
        $this->campaign('FB A', 'facebook', 100, 4, 1, 300);
        $this->campaign('FB B', 'facebook', 100, 1, 0, 0);
        $this->campaign('Search', 'google', 200, 2, 1, 1000);

        $report = app(OperatingMarketing::class)->performance($this->operatingUser('marketing'));

        $facebook = collect($report['channels'])->firstWhere('channel', 'facebook');
        $this->assertSame(200.0, $facebook['spend']);
        $this->assertSame(5, $facebook['leads']);
        $this->assertSame(40.0, $facebook['cpl']);
        $this->assertSame(50.0, $facebook['roi']);
        $this->assertSame('google', $report['best']['channel']);
        $this->assertSame(400.0, $report['best']['roi']);
        $this->assertSame('facebook', $report['worst']['channel']);
        $this->assertSame(['2026-10-01', '2026-10-15'], [$report['from'], $report['to']]);
    }

    public function test_totals_never_mix_currencies(): void
    {
        $this->campaign('USD', 'facebook', 100, 1, 1, 500);
        $this->campaign('EGP', 'facebook', 5000, 1, 1, 20000, 'EGP');

        $totals = collect(app(OperatingMarketing::class)->performance($this->operatingUser('owner'))['totals'])->keyBy('currency');

        $this->assertSame(100.0, $totals['USD']['spend']);
        $this->assertSame(5000.0, $totals['EGP']['spend']);
        $this->assertSame(300.0, $totals['EGP']['roi']);
    }

    public function test_sources_count_every_lead_created_in_the_period(): void
    {
        $this->operatingRecord('opportunities', 'won', ['lead_company' => 'A', 'lead_source' => 'referral', 'expected_value' => 800, 'currency' => 'USD'], 'Referral lead');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'B', 'lead_source' => 'referral'], 'Referral lead 2');

        $referral = collect(app(OperatingMarketing::class)->performance($this->operatingUser('marketing'))['sources'])->firstWhere('source', 'referral');

        $this->assertSame(2, $referral['leads']);
        $this->assertSame(50.0, $referral['conversion']);
        $this->assertSame(['USD' => 800.0], $referral['won_value']);
    }

    public function test_the_report_page_csv_and_access(): void
    {
        $this->campaign('Ramadan ERP', 'facebook', 100, 2, 1, 300);
        $marketing = $this->operatingUser('marketing');

        $this->actingAs($marketing)->get('/admin/operations/campaigns/performance')->assertOk()->assertSee('Ramadan ERP')->assertSee(__('مقارنة القنوات'));
        $csv = $this->actingAs($marketing)->get('/admin/operations/campaigns/performance?format=csv');
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Ramadan ERP', $content);

        $this->actingAs($marketing)->get('/admin/operations/campaigns/performance?from=2026-10-10&to=2026-10-01')->assertSessionHasErrors('to');
        $this->actingAs($this->operatingUser('developer'))->get('/admin/operations/campaigns/performance')->assertForbidden();
    }
}
