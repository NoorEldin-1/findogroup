<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class QuoteDocumentTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $clientId;

    private function quote(string $status): int
    {
        $this->clientId = $this->operatingRecord('clients', 'active', ['company' => 'Acme Corp', 'decision_maker' => 'Mona', 'contact' => 'Mona Ali', 'email' => 'mona@acme.test', 'tax_number' => 'TX-9'], 'Acme Corp');
        $lead = $this->operatingRecord('opportunities', 'proposal', ['client_id' => $this->clientId, 'need' => 'Customer portal', 'requested_date' => '2026-12-15'], 'Portal lead');

        return $this->operatingRecord('quotes', $status, ['opportunity_id' => $lead, 'currency' => 'USD', 'payment_terms' => '50% upfront', 'quote_number' => 'Q-2026-0007', 'valid_until' => '2026-11-01', 'price' => 1140, 'line_items' => [['description' => 'Portal build', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 1000, 'discount' => 0, 'tax' => 14]], 'totals' => ['subtotal' => 1000, 'line_discount' => 0, 'net' => 1000, 'tax' => 140, 'discount' => 0, 'total' => 1140]], 'Portal quote');
    }

    public function test_the_printable_document_shows_client_lines_and_totals(): void
    {
        $id = $this->quote('approved');

        $this->actingAs($this->operatingUser('sales_manager'))->get("/admin/operations/quotes/{$id}/print")
            ->assertOk()->assertSee('Q-2026-0007')->assertSee('Acme Corp')->assertSee('TX-9')->assertSee('Portal build')->assertSee('1,140.00')->assertDontSee('data-draft', false);
    }

    public function test_unapproved_quotes_are_watermarked_and_outsiders_are_blocked(): void
    {
        $id = $this->quote('draft');

        $this->actingAs($this->operatingUser('sales_manager'))->get("/admin/operations/quotes/{$id}/print")->assertOk()->assertSee('data-draft', false);
        $this->actingAs($this->operatingUser('developer'))->get("/admin/operations/quotes/{$id}/print")->assertForbidden();
        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/quotes/{$this->clientId}/print")->assertNotFound();
    }

    public function test_a_rep_only_reaches_quotes_on_their_own_leads(): void
    {
        $rep = $this->operatingUser('sales');
        $others = $this->quote('approved');
        $ownLead = $this->operatingRecord('opportunities', 'proposal', ['lead_company' => 'Mine'], 'My lead', $rep->id);
        $own = $this->operatingRecord('quotes', 'draft', ['opportunity_id' => $ownLead, 'price' => 10, 'currency' => 'USD', 'payment_terms' => 'Net 30'], 'My quote');

        $this->actingAs($rep)->get("/admin/operations/quotes/{$others}/print")->assertForbidden();
        $this->actingAs($rep)->get("/admin/operations/quotes/{$own}/print")->assertOk()->assertSee('Mine')->assertDontSee('Acme Corp');
        $this->assertSame(['My quote'], collect($this->actingAs($rep)->getJson('/admin/operations/quotes?format=json')->json('data'))->pluck('title')->all());
    }

    public function test_an_approved_quote_prefills_a_project_and_a_contract(): void
    {
        $id = $this->quote('approved');
        $owner = $this->operatingUser('owner');

        $this->actingAs($owner)->get("/admin/operations/projects/create?quote={$id}")->assertOk()->assertSee('Customer portal')->assertSee('2026-12-15')->assertSee('value="Portal quote"', false)->assertSee('<option value="'.$id.'" selected', false);
        $this->actingAs($owner)->get("/admin/operations/contracts/create?quote={$id}")->assertOk()->assertSee('Portal build');
        $this->actingAs($owner)->get("/admin/operations/record/{$id}")->assertOk()->assertSee("/admin/operations/projects/create?quote={$id}", false)->assertSee("/admin/operations/quotes/{$id}/print", false);
    }

    public function test_conversion_requires_an_approved_quote_and_happens_once(): void
    {
        $owner = $this->operatingUser('owner');
        $draft = $this->quote('draft');
        $this->actingAs($owner)->get("/admin/operations/projects/create?quote={$draft}")->assertSessionHasErrors('workflow');

        $approved = $this->quote('approved');
        $project = ['title' => 'Portal', 'reason' => 'Converted from quote', 'data' => ['client_id' => $this->clientId, 'quote_id' => $approved, 'outcome' => 'Customer portal', 'original_due' => '2026-12-15', 'client_contact' => 'Mona Ali']];
        $this->actingAs($owner)->post('/admin/operations/projects', $project)->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/admin/operations/projects', $project)->assertSessionHasErrors('workflow');
        $this->actingAs($owner)->get("/admin/operations/record/{$approved}")->assertOk()->assertDontSee("/admin/operations/projects/create?quote={$approved}", false);

        $project['data']['quote_id'] = $draft;
        $this->actingAs($owner)->post('/admin/operations/projects', $project)->assertSessionHasErrors('workflow');
    }
}
