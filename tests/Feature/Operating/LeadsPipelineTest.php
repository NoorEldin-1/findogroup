<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingSales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class LeadsPipelineTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function lead(array $data = []): array
    {
        return ['title' => 'ERP lead', 'reason' => 'Inbound call', 'data' => array_merge(['lead_company' => 'Nile Foods', 'lead_contact' => 'Omar', 'lead_phone' => '01000000000', 'lead_email' => 'omar@nile.test', 'need' => 'ERP system', 'solution' => 'web', 'lead_source' => 'facebook', 'next_action' => 'Discovery call', 'follow_up' => '2026-10-12', 'expected_value' => 5000, 'currency' => 'USD', 'probability' => 40], $data)];
    }

    public function test_a_lead_needs_a_client_or_a_company_but_not_an_existing_client(): void
    {
        $sales = $this->operatingUser('sales');

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead())->assertSessionHasNoErrors();
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['lead_company' => null, 'lead_phone' => null]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['probability' => 120]))->assertSessionHasErrors('workflow');
    }

    public function test_reps_cannot_assign_leads_to_others_but_the_manager_can(): void
    {
        $sales = $this->operatingUser('sales');
        $own = $this->employeeFor($sales);
        $colleague = $this->employeeFor($this->operatingUser('sales'));

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['assignee_id' => $own]))->assertSessionHasNoErrors();
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['assignee_id' => $colleague]))->assertSessionHasErrors('workflow');
        $this->actingAs($this->operatingUser('sales_manager'))->post('/admin/operations/opportunities', $this->lead(['assignee_id' => $colleague, 'lead_phone' => '01000000001']))->assertSessionHasNoErrors();
        $this->actingAs($sales)->get('/admin/operations/opportunities/create')->assertOk()->assertSee('<option value="'.$own.'"', false)->assertDontSee('<option value="'.$colleague.'"', false);
    }

    public function test_proposal_requires_a_client_and_won_requires_a_value(): void
    {
        $owner = $this->operatingUser('owner');
        $id = $this->operatingRecord('opportunities', 'qualified', ['lead_company' => 'Nile Foods', 'expected_value' => null, 'need' => 'ERP', 'solution' => 'web'], 'Lead');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'proposal', 'version' => 1, 'reason' => 'Send offer'])->assertSessionHasErrors('workflow');

        $clientId = $this->operatingRecord('clients', 'active', ['company' => 'Nile Foods', 'decision_maker' => 'Omar', 'contact' => '0100'], 'Nile Foods');
        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(['lead_company' => 'Nile Foods', 'client_id' => $clientId, 'expected_value' => null, 'need' => 'ERP', 'solution' => 'web'])]);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'proposal', 'version' => 1, 'reason' => 'Send offer'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'won', 'version' => 2, 'reason' => 'Signed'])->assertSessionHasErrors('workflow');
    }

    public function test_converting_a_lead_creates_and_links_a_client_once(): void
    {
        $sales = $this->operatingUser('sales');
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead());
        $leadId = DB::table('operating_records')->where('type', 'opportunities')->value('id');

        $this->actingAs($sales)->post("/admin/operations/opportunities/{$leadId}/convert-client", ['reason' => 'Qualified'])->assertRedirect();

        $client = DB::table('operating_records')->where('type', 'clients')->sole();
        $this->assertSame('Nile Foods', $client->title);
        $this->assertSame('omar@nile.test', json_decode($client->data, true)['email']);
        $this->assertSame($client->id, $this->recordData($leadId)['client_id']);
        $this->assertTrue(DB::table('operating_links')->where('record_id', $leadId)->where('target_id', $client->id)->exists());

        $this->actingAs($sales)->post("/admin/operations/opportunities/{$leadId}/convert-client", ['reason' => 'Again'])->assertSessionHasErrors('workflow');
    }

    public function test_a_lead_created_before_lead_sources_can_still_be_converted(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $id = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Legacy Co', 'lead_contact' => 'Sara', 'need' => 'Site', 'solution' => 'web', 'source' => 'old text', 'next_action' => 'Call', 'follow_up' => '2026-10-12'], 'Legacy lead');

        $this->actingAs($manager)->post("/admin/operations/opportunities/{$id}/convert-client", ['reason' => 'Qualified'])->assertSessionHasNoErrors();
        $this->assertSame('other', $this->recordData($id)['lead_source']);
    }

    public function test_pipeline_groups_open_stages_and_computes_the_weighted_forecast(): void
    {
        $owner = $this->operatingUser('owner');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'A', 'expected_value' => 1000, 'currency' => 'USD', 'probability' => 50, 'follow_up' => '2020-01-01'], 'A');
        $this->operatingRecord('opportunities', 'proposal', ['lead_company' => 'B', 'expected_value' => 2000, 'currency' => 'USD', 'probability' => 25], 'B');
        $this->operatingRecord('opportunities', 'lost', ['lead_company' => 'C', 'expected_value' => 9000, 'currency' => 'USD', 'probability' => 90], 'C');

        $pipeline = app(OperatingSales::class)->pipeline($owner);

        $this->assertSame(1000.0, $pipeline['summary']['weighted']['USD']);
        $this->assertSame(2, $pipeline['summary']['open']);
        $this->assertSame(1, $pipeline['columns']['new']['count']);
        $this->assertTrue($pipeline['columns']['new']['cards'][0]['overdue']);
        $this->assertSame(2000.0, $pipeline['columns']['proposal']['value']['USD']);
        $this->actingAs($owner)->get('/admin/operations/opportunities/pipeline')->assertOk()->assertSee('2,000.00')->assertSee('1,000.00 USD');
    }
}
