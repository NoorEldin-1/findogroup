<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmQuickActivityTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_a_rep_logs_a_call_with_result_and_a_next_follow_up(): void
    {
        $this->travelTo('2026-10-20 11:00:00');
        $rep = $this->operatingUser('sales');
        $employee = $this->employeeFor($rep);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'assignee_id' => $employee, 'sla_due_at' => '2026-10-20T12:00'], 'Nile Foods', $rep->id);

        $this->actingAs($rep)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'call', 'result' => 'interested', 'outcome' => 'Wants a demo', 'next_step' => 'Send deck', 'follow_up_at' => '2026-10-22T10:00'])->assertSessionHasNoErrors()->assertRedirect();

        $done = DB::table('operating_records')->where('type', 'activities')->where('status', 'done')->sole();
        $doneData = json_decode($done->data, true);
        $this->assertSame(['call', 'interested', $lead, $employee], [$doneData['kind'], $doneData['result'], $doneData['opportunity_id'], $doneData['assignee_id']]);
        $planned = json_decode(DB::table('operating_records')->where('type', 'activities')->where('status', 'planned')->sole()->data, true);
        $this->assertSame(['follow_up', '2026-10-22T10:00'], [$planned['kind'], $planned['due_at']]);
        $this->assertSame('2026-10-20T11:00', $this->recordData($lead)['first_contact_at']);
    }

    public function test_an_outcome_is_required_and_strangers_are_refused(): void
    {
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods'], 'Nile Foods', $rep->id);

        $this->actingAs($rep)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'call'])->assertSessionHasErrors('outcome');
        $stranger = $this->operatingUser('sales');
        $this->employeeFor($stranger);
        $this->actingAs($stranger)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'call', 'outcome' => 'x'])->assertForbidden();
    }

    public function test_an_owner_without_an_employee_file_logs_on_behalf_of_the_lead_owner(): void
    {
        $owner = $this->operatingUser('owner');
        $employee = $this->employeeFor($this->operatingUser('sales'));
        $lead = $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Nile Foods', 'assignee_id' => $employee], 'Nile Foods');

        $this->actingAs($owner)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'note', 'outcome' => 'Budget approved'])->assertSessionHasNoErrors();

        $this->assertSame($employee, json_decode(DB::table('operating_records')->where('type', 'activities')->sole()->data, true)['assignee_id']);
    }
}
