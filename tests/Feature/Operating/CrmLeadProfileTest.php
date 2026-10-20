<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadProfileTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_the_owner_rep_sees_journey_activity_result_and_sla(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $employee = $this->employeeFor($rep);
        $lead = $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Nile Foods', 'lead_phone' => '01001234567', 'lead_quality' => 'hot', 'assignee_id' => $employee, 'sla_due_at' => '2026-10-20T10:00', 'first_contact_at' => '2026-10-20T09:00'], 'Nile Foods', $rep->id);
        $this->transitionEvent($lead, 'contacted', '2026-10-20 09:00:00');
        $this->transitionEvent($lead, 'meeting', '2026-10-20 09:30:00');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'result' => 'interested', 'outcome' => 'Wants a demo', 'assignee_id' => $employee, 'due_at' => '2026-10-20T09:00', 'completed_at' => '2026-10-20T09:00', 'opportunity_id' => $lead], 'Call — Nile Foods');

        $this->actingAs($rep)->get("/admin/operations/crm/leads/{$lead}")->assertOk()
            ->assertSee('Nile Foods')->assertSee(__('اجتماع محدد'))->assertSee('Wants a demo')->assertSee(__('مهتم'))
            ->assertSee(__('في الموعد'))->assertSee(__('ساخن'))->assertSee('01001234567');
    }

    public function test_other_reps_cannot_open_it_and_non_leads_404(): void
    {
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods'], 'Nile Foods');
        $client = $this->operatingRecord('clients', 'active', ['company' => 'X', 'decision_maker' => 'Y', 'contact' => 'Z'], 'X');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get("/admin/operations/crm/leads/{$lead}")->assertForbidden();
        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/crm/leads/{$client}")->assertNotFound();
    }

    public function test_a_manager_hands_a_lead_over_with_a_note_and_a_rep_cannot(): void
    {
        $first = $this->employeeFor($this->operatingUser('sales'));
        $second = $this->employeeFor($this->operatingUser('sales'));
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook', 'assignee_id' => $first], 'Nile Foods');

        $this->actingAs($this->operatingUser('sales'))->post("/admin/operations/crm/leads/{$lead}/handover", ['assignee_id' => $second, 'note' => 'x'])->assertForbidden();
        $this->actingAs($this->operatingUser('sales_manager'))->post("/admin/operations/crm/leads/{$lead}/handover", ['assignee_id' => $second, 'note' => 'Speaks German'])->assertSessionHasNoErrors();

        $this->assertSame($second, $this->recordData($lead)['assignee_id']);
        $this->assertTrue(DB::table('operating_events')->where('record_id', $lead)->where('reason', 'like', '%Speaks German%')->exists());
    }
}
