<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadModelTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function quickLead(array $data = []): array
    {
        return ['title' => 'Omar Hassan', 'reason' => 'Inbound WhatsApp', 'data' => array_merge(['lead_phone' => '+20 100 123 4567', 'lead_source' => 'facebook', 'lead_quality' => 'hot', 'fit_status' => 'review', 'lead_channel' => 'whatsapp', 'country' => 'Egypt', 'business_profile' => 'Restaurant'], $data)];
    }

    public function test_a_lead_can_be_created_with_contact_details_only(): void
    {
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead())->assertSessionHasNoErrors();

        $lead = DB::table('operating_records')->where('type', 'opportunities')->sole();
        $data = json_decode($lead->data, true);
        $this->assertSame('new', $lead->status);
        $this->assertSame('001234567', $data['phone_key']);
        $this->assertSame('hot', $data['lead_quality']);
    }

    public function test_the_same_phone_in_another_format_is_rejected_as_a_duplicate(): void
    {
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead())->assertSessionHasNoErrors();

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead(['lead_phone' => '01001234567']))->assertSessionHasErrors('workflow');
        $this->assertSame(1, DB::table('operating_records')->where('type', 'opportunities')->count());
    }

    public function test_an_archived_lead_does_not_block_its_phone(): void
    {
        $this->operatingRecord('opportunities', 'archived', ['lead_company' => 'Old', 'lead_phone' => '01001234567', 'phone_key' => '001234567'], 'Old lead');
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead())->assertSessionHasNoErrors();
    }

    public function test_qualifying_requires_need_and_solution(): void
    {
        $owner = $this->operatingUser('owner');
        $id = $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Nile Foods'], 'Lead');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'qualified', 'version' => 1, 'reason' => 'Fit'])->assertSessionHasErrors('workflow');

        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(['lead_company' => 'Nile Foods', 'need' => 'ERP', 'solution' => 'web'])]);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'qualified', 'version' => 1, 'reason' => 'Fit'])->assertSessionHasNoErrors();
    }

    public function test_leaving_new_records_first_contact_and_closing_records_closed_at(): void
    {
        $owner = $this->operatingUser('owner');
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Nile Foods', 'decision_maker' => 'Omar', 'contact' => '0100'], 'Nile Foods');
        $id = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'client_id' => $client, 'need' => 'ERP', 'solution' => 'web', 'expected_value' => 1000], 'Lead');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'meeting', 'version' => 1, 'reason' => 'Booked'])->assertSessionHasNoErrors();
        $this->assertNotEmpty($this->recordData($id)['first_contact_at']);
        $this->assertArrayNotHasKey('closed_at', $this->recordData($id));

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'won', 'version' => 2, 'reason' => 'Signed'])->assertSessionHasNoErrors();
        $this->assertNotEmpty($this->recordData($id)['closed_at']);
    }

    public function test_a_completed_call_counts_as_first_contact_but_a_note_does_not(): void
    {
        $owner = $this->operatingUser('owner');
        $employee = $this->employeeFor($owner);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods'], 'Lead');
        $note = $this->operatingRecord('activities', 'planned', ['kind' => 'note', 'assignee_id' => $employee, 'due_at' => '2026-10-20T10:00', 'opportunity_id' => $lead, 'outcome' => 'Internal'], 'Note');
        $call = $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-20T10:00', 'opportunity_id' => $lead, 'outcome' => 'Interested'], 'Call');

        $this->actingAs($owner)->post("/admin/operations/record/{$note}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Logged'])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('first_contact_at', $this->recordData($lead));

        $this->actingAs($owner)->post("/admin/operations/record/{$call}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Logged'])->assertSessionHasNoErrors();
        $this->assertNotEmpty($this->recordData($lead)['first_contact_at']);
    }

    public function test_stage_reach_counts_later_stages_as_passing_earlier_ones(): void
    {
        $crm = app(OperatingCrm::class);
        $jumped = $this->operatingRecord('opportunities', 'won', ['lead_company' => 'A'], 'A');
        $lostAfterMeeting = $this->operatingRecord('opportunities', 'lost', ['lead_company' => 'B'], 'B');
        $this->transitionEvent($lostAfterMeeting, 'meeting');
        $fresh = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'C'], 'C');

        $reach = $crm->stageReach([$jumped, $lostAfterMeeting, $fresh]);

        $this->assertTrue($crm->reached($reach[$jumped], 'meeting'));
        $this->assertTrue($crm->reached($reach[$lostAfterMeeting], 'contacted'));
        $this->assertFalse($crm->reached($reach[$lostAfterMeeting], 'won'));
        $this->assertFalse($crm->reached($reach[$fresh], 'contacted'));
    }

    public function test_backfill_fills_phone_keys_for_legacy_leads(): void
    {
        $id = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Legacy', 'lead_phone' => '0100 123 4567'], 'Legacy');

        $this->artisan('operating:crm-backfill')->assertSuccessful();

        $this->assertSame('001234567', $this->recordData($id)['phone_key']);
    }
}
