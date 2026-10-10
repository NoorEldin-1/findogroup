<?php

namespace Tests\Feature\Operating;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class QuoteLinesTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $leadId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');
        $this->leadId = $this->operatingRecord('opportunities', 'proposal', ['client_id' => $client, 'lead_company' => 'Acme'], 'ERP lead');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function quote(array $data = []): array
    {
        return ['title' => 'ERP quote', 'reason' => 'Draft offer', 'data' => array_merge(['opportunity_id' => $this->leadId, 'currency' => 'USD', 'payment_terms' => '50% upfront', 'discount' => 100, 'line_items' => [
            ['description' => 'ERP implementation', 'kind' => 'service', 'quantity' => 2, 'unit_price' => 500, 'discount' => 10, 'tax' => 14],
            ['description' => 'License', 'kind' => 'product', 'quantity' => 1, 'unit_price' => 1000, 'discount' => 0, 'tax' => 0],
            ['description' => '', 'kind' => 'service', 'quantity' => null, 'unit_price' => null, 'discount' => null, 'tax' => null],
        ]], $data)];
    }

    public function test_lines_are_normalised_and_totals_are_computed_into_price(): void
    {
        $this->actingAs($this->operatingUser('sales_manager'))->post('/admin/operations/quotes', $this->quote())->assertSessionHasNoErrors();

        $data = json_decode(DB::table('operating_records')->where('type', 'quotes')->value('data'), true);
        $this->assertCount(2, $data['line_items']);
        $this->assertEquals(['subtotal' => 2000, 'line_discount' => 100, 'net' => 1900, 'tax' => 126, 'discount' => 100, 'total' => 1926], $data['totals']);
        $this->assertEquals(1926, $data['price']);
        $this->assertSame('Q-2026-0001', $data['quote_number']);
    }

    public function test_a_rep_can_quote_only_their_own_leads(): void
    {
        $rep = $this->operatingUser('sales');
        $own = $this->operatingRecord('opportunities', 'proposal', ['lead_company' => 'Mine'], 'My lead', $rep->id);

        $this->actingAs($rep)->post('/admin/operations/quotes', $this->quote(['opportunity_id' => $own]))->assertSessionHasNoErrors();
        $this->actingAs($rep)->post('/admin/operations/quotes', $this->quote())->assertForbidden();
    }

    public function test_quote_numbers_are_sequential_per_year(): void
    {
        $sales = $this->operatingUser('sales_manager');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote());
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote());

        $numbers = DB::table('operating_records')->where('type', 'quotes')->orderBy('id')->pluck('data')->map(fn (string $data): string => json_decode($data, true)['quote_number'])->all();
        $this->assertSame(['Q-2026-0001', 'Q-2026-0002'], $numbers);
    }

    public function test_invalid_lines_and_discounts_are_rejected(): void
    {
        $sales = $this->operatingUser('sales_manager');

        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['line_items' => [['description' => 'Zero', 'kind' => 'service', 'quantity' => 0, 'unit_price' => 10, 'discount' => 0, 'tax' => 0]]]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['discount' => 5000]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['line_items' => [], 'price' => null]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['line_items' => [], 'price' => 3000, 'discount' => null]))->assertSessionHasNoErrors();
    }

    public function test_approval_needs_an_approved_scope_when_linked_otherwise_line_items(): void
    {
        $owner = $this->operatingUser('owner');
        $line = [['description' => 'Audit', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0, 'tax' => 0]];
        $base = ['opportunity_id' => $this->leadId, 'price' => 100, 'currency' => 'USD', 'payment_terms' => 'Net 30', 'approval_evidence' => 'Signed by client'];
        $withLines = $this->operatingRecord('quotes', 'submitted', $base + ['line_items' => $line], 'With lines');
        $withoutLines = $this->operatingRecord('quotes', 'submitted', $base, 'No lines');
        $projectId = $this->operatingRecord('projects', 'discovery', ['outcome' => 'X', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Project');
        $draftScope = $this->operatingRecord('scopes', 'draft', ['project_id' => $projectId, 'scope_version' => 1], 'Scope v1');
        $scoped = $this->operatingRecord('quotes', 'submitted', $base + ['scope_id' => $draftScope, 'line_items' => $line], 'Scoped');

        $this->actingAs($owner)->post("/admin/operations/record/{$withLines}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Client signed'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/admin/operations/record/{$withoutLines}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Client signed'])->assertSessionHasErrors('workflow');
        $this->actingAs($owner)->post("/admin/operations/record/{$scoped}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Client signed'])->assertSessionHasErrors('workflow');
    }

    public function test_approved_quote_lines_are_immutable(): void
    {
        $sales = $this->operatingUser('sales_manager');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote());
        $id = DB::table('operating_records')->where('type', 'quotes')->value('id');
        DB::table('operating_records')->where('id', $id)->update(['status' => 'approved']);

        $this->actingAs($sales)->post("/admin/operations/record/{$id}", $this->quote(['discount' => 0]) + ['version' => 1])->assertSessionHasErrors('workflow');
    }

    public function test_the_quote_form_renders_the_line_editor_with_saved_totals(): void
    {
        $sales = $this->operatingUser('sales_manager');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote());
        $id = DB::table('operating_records')->where('type', 'quotes')->value('id');

        $this->actingAs($sales)->get("/admin/operations/record/{$id}")->assertOk()->assertSee('data-line-items', false)->assertSee('ERP implementation')->assertSee('1,926.00');
        $this->actingAs($sales)->get('/admin/operations/quotes/create')->assertOk()->assertSee('data-line-items', false);
    }
}
