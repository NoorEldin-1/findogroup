<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmSlaBoardTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_compliance_breaches_activity_mix_and_the_breached_list(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->employeeFor($this->operatingUser('sales'));
        $lead = function (string $status, array $data, string $title) use ($rep): int {
            $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co', 'assignee_id' => $rep], $title);
            DB::table('operating_records')->where('id', $id)->update(['created_at' => '2026-10-10 08:00:00']);

            return $id;
        };
        $lead('contacted', ['sla_due_at' => '2026-10-10T10:00', 'first_contact_at' => '2026-10-10T09:00'], 'Met');
        $lead('contacted', ['sla_due_at' => '2026-10-10T10:00', 'first_contact_at' => '2026-10-10T11:00'], 'Late');
        $lead('new', ['sla_due_at' => '2026-10-10T10:00'], 'Breached');
        $lead('new', ['sla_due_at' => '2026-10-21T10:00'], 'Pending');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'result' => 'no_answer', 'assignee_id' => $rep, 'due_at' => '2026-10-10T09:00', 'completed_at' => '2026-10-10T09:00'], 'Call');
        $this->operatingRecord('activities', 'done', ['kind' => 'whatsapp', 'result' => 'interested', 'assignee_id' => $rep, 'due_at' => '2026-10-10T11:00', 'completed_at' => '2026-10-10T11:00'], 'WA');

        $board = app(OperatingCrmReports::class)->sla($this->operatingUser('sales_manager'), '2026-10-01', '2026-10-31', 'team');

        $this->assertSame(['pending' => 1, 'met' => 1, 'late' => 1, 'breached' => 1], $board['states']);
        $this->assertSame(33.3, $board['compliance']);
        $this->assertSame(2.0, $board['avg_response']);
        $this->assertSame(2, $board['activities']);
        $this->assertSame(['call' => 1, 'whatsapp' => 1], $board['kinds']);
        $this->assertSame(['no_answer' => 1, 'interested' => 1], $board['results']);
        $this->assertSame(['Breached'], array_column($board['breached'], 'title'));
        $this->assertSame(242.0, $board['breached'][0]['hours']);
        $this->assertSame(33.3, $board['trend']['2026-10-10']);
    }

    public function test_the_page_renders_for_reps_with_their_own_numbers(): void
    {
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin/operations/crm/sla')->assertOk()->assertSee(__('نسبة الالتزام بـSLA'));
    }
}
