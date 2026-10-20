<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmFunnelTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function lead(string $status, array $data, string $createdAt): int
    {
        $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co'], 'Lead '.$createdAt);
        DB::table('operating_records')->where('id', $id)->update(['created_at' => $createdAt]);

        return $id;
    }

    public function test_stage_counts_rates_response_time_and_campaigns(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $campaign = $this->operatingRecord('campaigns', 'running', ['objective' => 'leads', 'budget_cap' => 1000], 'Ramadan');
        $this->lead('new', ['campaign_id' => $campaign], '2026-10-01 10:00:00');
        $this->lead('contacted', ['campaign_id' => $campaign, 'first_contact_at' => '2026-10-02T12:00'], '2026-10-02 10:00:00');
        $won = $this->lead('won', ['campaign_id' => $campaign, 'expected_value' => 3000, 'currency' => 'USD', 'closed_at' => '2026-10-12T10:00', 'first_contact_at' => '2026-10-03T14:00'], '2026-10-03 10:00:00');
        $this->transitionEvent($won, 'meeting');
        $this->lead('meeting', [], '2026-10-04 10:00:00');

        $funnel = app(OperatingCrmReports::class)->funnel($this->operatingUser('sales_manager'), '2026-10-01', '2026-10-31', 'team');

        $stages = collect($funnel['stages'])->keyBy('stage');
        $this->assertSame([4, 3, 2, 1], [$stages['new']['count'], $stages['contacted']['count'], $stages['meeting']['count'], $stages['won']['count']]);
        $this->assertSame(75.0, $stages['contacted']['rate']);
        $this->assertSame(66.7, $stages['meeting']['rate']);
        $this->assertSame(3.0, $funnel['avg_response']);
        $this->assertSame(1, $funnel['won']);
        $this->assertEquals(['USD' => 3000.0], $funnel['won_value']);
        $this->assertSame(3, $funnel['potential']['count']);
        $this->assertCount(31, $funnel['trend']);
        $this->assertSame(1, $funnel['trend']['2026-10-03']);
        $this->assertCount(12, $funnel['months']);
        $this->assertSame(1, $funnel['months']['2026-10']['count']);
        $ramadan = collect($funnel['campaigns'])->firstWhere('name', 'Ramadan');
        $this->assertSame([3, 2, 1, 1, 33.3], [$ramadan['leads'], $ramadan['contacted'], $ramadan['meetings'], $ramadan['won'], $ramadan['conversion']]);
    }

    public function test_the_page_renders(): void
    {
        $this->actingAs($this->operatingUser('sales_manager'))->get('/admin/operations/crm/funnel')->assertOk()->assertSee(__('معدلات التحويل بين المراحل'));
    }
}
