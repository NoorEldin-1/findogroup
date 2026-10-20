<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmSlaTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_a_new_lead_gets_a_due_time_from_the_configured_hours(): void
    {
        $this->travelTo('2026-10-20 09:00:00');
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', ['title' => 'Lead', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01001234567', 'lead_source' => 'facebook']])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-21T09:00', $this->recordData(DB::table('operating_records')->where('type', 'opportunities')->value('id'))['sla_due_at']);
    }

    public function test_no_due_time_is_set_when_sla_is_disabled(): void
    {
        DB::table('operating_settings')->insert(['key' => 'crm', 'data' => json_encode(['sla_enabled' => false]), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', ['title' => 'Lead', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01001234567', 'lead_source' => 'facebook']]);

        $this->assertNull($this->recordData(DB::table('operating_records')->where('type', 'opportunities')->value('id'))['sla_due_at'] ?? null);
    }

    public function test_states_cover_pending_met_late_and_breached(): void
    {
        $crm = app(OperatingCrm::class);
        $lead = fn (string $status, array $data): object => DB::table('operating_records')->find($this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'X'], 'X'));
        $now = '2026-10-20T12:00';

        $this->assertSame('pending', $crm->slaState($lead('new', ['sla_due_at' => '2026-10-20T13:00']), $now));
        $this->assertSame('breached', $crm->slaState($lead('new', ['sla_due_at' => '2026-10-20T11:00']), $now));
        $this->assertSame('met', $crm->slaState($lead('contacted', ['sla_due_at' => '2026-10-20T11:00', 'first_contact_at' => '2026-10-20T10:30']), $now));
        $this->assertSame('late', $crm->slaState($lead('contacted', ['sla_due_at' => '2026-10-20T11:00', 'first_contact_at' => '2026-10-20T11:30']), $now));
        $this->assertNull($crm->slaState($lead('lost', ['sla_due_at' => '2026-10-20T11:00']), $now));
        $this->assertNull($crm->slaState($lead('new', []), $now));
    }

    public function test_the_alert_goes_once_to_the_owner_their_manager_and_sales_managers(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $managerUser = $this->operatingUser('delivery');
        $managerEmployee = $this->employeeFor($managerUser);
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser, ['manager_id' => $managerEmployee]);
        $salesManager = $this->operatingUser('sales_manager');
        $breached = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Late Co', 'assignee_id' => $rep, 'sla_due_at' => '2026-10-20T11:00'], 'Late Co');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Fine Co', 'assignee_id' => $rep, 'sla_due_at' => '2026-10-20T13:00'], 'Fine Co');
        $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Done Co', 'assignee_id' => $rep, 'sla_due_at' => '2026-10-20T11:00', 'first_contact_at' => '2026-10-20T10:00'], 'Done Co');

        $this->artisan('operating:crm-sla')->assertSuccessful();
        $this->artisan('operating:crm-sla')->assertSuccessful();

        foreach ([$repUser, $managerUser, $salesManager] as $user) {
            $this->assertSame(1, $user->notifications()->count());
            $this->assertSame('crm_sla', $user->notifications()->first()->data['kind']);
        }
        $this->assertSame('2026-10-20T12:00', $this->recordData($breached)['sla_breached_at']);
    }

    public function test_only_managers_dismiss_an_open_alert_with_a_reason(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Late Co', 'sla_due_at' => '2026-10-20T11:00'], 'Late Co');

        $this->actingAs($this->operatingUser('sales'))->post("/admin/operations/crm/leads/{$lead}/sla-dismiss", ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->operatingUser('sales_manager'))->post("/admin/operations/crm/leads/{$lead}/sla-dismiss", ['reason' => 'Client asked to call next week'])->assertSessionHasNoErrors();

        $this->assertFalse(app(OperatingCrm::class)->openAlert(DB::table('operating_records')->find($lead)));
        $this->assertTrue(DB::table('operating_events')->where('record_id', $lead)->where('reason', 'like', '%Client asked%')->exists());
    }
}
