<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmTeamTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_each_agent_gets_leads_wins_revenue_sla_and_activity_counts(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $amira = $this->employeeFor($this->operatingUser('sales'));
        DB::table('operating_records')->where('id', $amira)->update(['title' => 'Amira']);
        $lead = function (string $status, array $data) {
            $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co'], 'Lead');
            DB::table('operating_records')->where('id', $id)->update(['created_at' => '2026-10-05 10:00:00']);
        };
        $lead('won', ['assignee_id' => $amira, 'expected_value' => 2000, 'currency' => 'USD', 'lead_quality' => 'hot']);
        $lead('lost', ['assignee_id' => $amira, 'lead_quality' => 'cold']);
        $lead('new', ['assignee_id' => $amira, 'sla_due_at' => '2026-10-05T12:00']);
        $lead('new', []);
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'assignee_id' => $amira, 'due_at' => '2026-10-06T10:00', 'completed_at' => '2026-10-06T10:00', 'client_id' => null, 'opportunity_id' => null], 'Call');

        $team = app(OperatingCrmReports::class)->team($this->operatingUser('sales_manager'), '2026-10-01', '2026-10-31');

        $row = collect($team['agents'])->firstWhere('name', 'Amira');
        $this->assertSame([3, 1, 1, 1, 50.0, 1, 1], [$row['leads'], $row['open'], $row['won'], $row['lost'], $row['win_rate'], $row['sla_breaches'], $row['activities']]);
        $this->assertEquals(['USD' => 2000.0], $row['revenue']);
        $this->assertSame(1, collect($team['agents'])->firstWhere('name', __('غير مسند'))['leads']);
        $this->assertSame(2, $team['stages'][__('جديد')]);
        $this->assertSame(['hot' => 1, 'cold' => 1, 'unknown' => 2], array_intersect_key($team['quality'], array_flip(['hot', 'cold', 'unknown'])));
    }

    public function test_reps_cannot_open_the_team_board(): void
    {
        $this->actingAs($this->operatingUser('sales'))->get('/admin/operations/crm/team')->assertForbidden();
        $this->actingAs($this->operatingUser('sales_manager'))->get('/admin/operations/crm/team')->assertOk()->assertSee(__('أداء الفريق'));
    }
}
