<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @return array{client: int, invoice: int}
     */
    private function seedClient(): array
    {
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100', 'phone' => '0222333'], 'Acme');
        $this->operatingRecord('contacts', 'active', ['client_id' => $client, 'position' => 'CTO', 'is_primary' => true], 'Mona CTO');
        $lead = $this->operatingRecord('opportunities', 'won', ['client_id' => $client, 'expected_value' => 5000, 'currency' => 'USD'], 'ERP deal');
        $this->operatingRecord('quotes', 'approved', ['opportunity_id' => $lead, 'price' => 5000, 'currency' => 'USD'], 'ERP quote');
        $this->operatingRecord('projects', 'execution', ['client_id' => $client, 'outcome' => 'ERP', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'ERP project');
        $this->operatingRecord('activities', 'done', ['kind' => 'meeting', 'client_id' => $client, 'due_at' => '2026-10-01T10:00', 'outcome' => 'Kickoff'], 'Kickoff meeting');
        $invoice = $this->operatingRecord('invoices', 'part_paid', ['client_id' => $client, 'invoice_number' => 'INV-1', 'amount' => 1000, 'currency' => 'USD', 'due_date' => '2026-10-20'], 'INV-1');
        $this->operatingRecord('receipts', 'active', ['invoice_id' => $invoice, 'amount' => 400, 'received_at' => '2026-10-05', 'reference' => 'TRX'], 'Receipt');

        return ['client' => $client, 'invoice' => $invoice];
    }

    public function test_owner_sees_the_full_profile_with_outstanding_balance(): void
    {
        $ids = $this->seedClient();

        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/clients/{$ids['client']}/profile")
            ->assertOk()->assertSee('0222333')->assertSee('Mona CTO')->assertSee('ERP deal')->assertSee('ERP quote')->assertSee('ERP project')->assertSee('Kickoff meeting')->assertSee('INV-1')->assertSee('600.00 USD')->assertSee('5,000.00 USD');
    }

    public function test_sales_does_not_see_finance_sections(): void
    {
        $ids = $this->seedClient();

        $this->actingAs($this->operatingUser('sales_manager'))->get("/admin/operations/clients/{$ids['client']}/profile")
            ->assertOk()->assertSee('ERP deal')->assertDontSee('INV-1')->assertDontSee('600.00');
    }

    public function test_profile_rejects_other_types_and_unauthorised_roles(): void
    {
        $ids = $this->seedClient();

        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/clients/{$ids['invoice']}/profile")->assertNotFound();
        $this->actingAs($this->operatingUser('developer'))->get("/admin/operations/clients/{$ids['client']}/profile")->assertForbidden();
    }

    public function test_a_client_has_one_primary_contact(): void
    {
        $ids = $this->seedClient();
        $payload = ['title' => 'Second', 'reason' => 'New contact', 'data' => ['client_id' => $ids['client'], 'is_primary' => true]];

        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/contacts', $payload)->assertSessionHasErrors('workflow');
        $this->actingAs($this->operatingUser('sales_manager'))->post('/admin/operations/contacts', ['title' => 'Form', 'reason' => 'UI', 'data' => ['client_id' => $ids['client'], 'is_primary' => '1']])->assertSessionHasErrors('workflow');
        $payload['data']['is_primary'] = false;
        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/contacts', $payload)->assertSessionHasNoErrors();
    }
}
