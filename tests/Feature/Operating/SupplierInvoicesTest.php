<?php

namespace Tests\Feature\Operating;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SupplierInvoicesTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private User $finance;

    private int $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finance = $this->operatingUser('finance');
        $this->supplier = $this->operatingRecord('suppliers', 'active', ['category' => 'saas'], 'Figma');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function invoice(array $data = []): array
    {
        return array_merge(['supplier_id' => $this->supplier, 'invoice_number' => 'INV-77', 'invoice_date' => '2026-10-01', 'amount' => 1000, 'currency' => 'USD', 'due_date' => '2026-10-31', 'evidence' => 'PDF in drive'], $data);
    }

    private function move(int $id, string $target): TestResponse
    {
        return $this->actingAs($this->finance)->post("/admin/operations/record/{$id}/transition", ['target' => $target, 'version' => DB::table('operating_records')->where('id', $id)->value('version'), 'reason' => 'Finance step']);
    }

    private function pay(int $invoice, float $amount): int
    {
        $this->actingAs($this->finance)->post('/admin/operations/supplier_payments', ['title' => 'Payment', 'reason' => 'Pay', 'data' => ['supplier_invoice_id' => $invoice, 'amount' => $amount, 'paid_at' => '2026-10-12', 'reference' => 'TRX-'.$amount]])->assertSessionHasNoErrors();

        return DB::table('operating_records')->where('type', 'supplier_payments')->max('id');
    }

    private function stateOf(int $id): string
    {
        return DB::table('operating_records')->where('id', $id)->value('status');
    }

    public function test_invoice_numbers_are_unique_per_supplier_and_orders_cap_billing(): void
    {
        $this->actingAs($this->finance)->post('/admin/operations/supplier_invoices', ['title' => 'Figma Oct', 'reason' => 'Bill', 'data' => $this->invoice()])->assertSessionHasNoErrors();
        $this->actingAs($this->finance)->post('/admin/operations/supplier_invoices', ['title' => 'Again', 'reason' => 'Bill', 'data' => $this->invoice(['invoice_number' => ' inv-77 '])])->assertSessionHasErrors('workflow');

        $order = $this->operatingRecord('purchase_orders', 'approved', ['supplier_id' => $this->supplier, 'amount' => 1500, 'currency' => 'USD', 'description' => 'Seats', 'order_date' => '2026-10-01', 'expected_delivery' => '2026-10-02'], 'PO');
        $this->actingAs($this->finance)->post('/admin/operations/supplier_invoices', ['title' => 'Part 1', 'reason' => 'Bill', 'data' => $this->invoice(['invoice_number' => 'A1', 'purchase_order_id' => $order])])->assertSessionHasNoErrors();
        $this->actingAs($this->finance)->post('/admin/operations/supplier_invoices', ['title' => 'Part 2', 'reason' => 'Bill', 'data' => $this->invoice(['invoice_number' => 'A2', 'purchase_order_id' => $order, 'amount' => 600])])->assertSessionHasErrors('workflow');
        $this->actingAs($this->finance)->post('/admin/operations/supplier_invoices', ['title' => 'Part 2', 'reason' => 'Bill', 'data' => $this->invoice(['invoice_number' => 'A2', 'purchase_order_id' => $order, 'amount' => 500, 'currency' => 'EGP'])])->assertSessionHasErrors('workflow');
        $this->actingAs($this->finance)->post('/admin/operations/supplier_invoices', ['title' => 'Part 2', 'reason' => 'Bill', 'data' => $this->invoice(['invoice_number' => 'A2', 'purchase_order_id' => $order, 'amount' => 500])])->assertSessionHasNoErrors();
    }

    public function test_payments_cannot_exceed_the_invoice_and_settle_its_status(): void
    {
        $invoice = $this->operatingRecord('supplier_invoices', 'approved', $this->invoice(), 'Figma Oct');

        $first = $this->pay($invoice, 400);
        $this->move($first, 'active')->assertSessionHasNoErrors();
        $this->assertSame('part_paid', $this->stateOf($invoice));
        $this->assertSame('USD', $this->recordData($first)['currency']);

        $this->actingAs($this->finance)->post('/admin/operations/supplier_payments', ['title' => 'Too much', 'reason' => 'Pay', 'data' => ['supplier_invoice_id' => $invoice, 'amount' => 700, 'paid_at' => '2026-10-12', 'reference' => 'X']])->assertSessionHasErrors('workflow');
        $second = $this->pay($invoice, 600);
        $this->move($second, 'active')->assertSessionHasNoErrors();
        $this->assertSame('paid', $this->stateOf($invoice));

        $this->move($second, 'archived')->assertSessionHasNoErrors();
        $this->assertSame('part_paid', $this->stateOf($invoice));
    }

    public function test_paid_status_is_never_set_by_hand_and_void_needs_no_payments(): void
    {
        $invoice = $this->operatingRecord('supplier_invoices', 'approved', $this->invoice(), 'Figma Oct');
        $this->move($invoice, 'paid')->assertSessionHasErrors('workflow');

        $payment = $this->pay($invoice, 100);
        $this->move($payment, 'active');
        $this->move($invoice, 'void')->assertSessionHasErrors('workflow');
        $this->move($invoice, 'archived')->assertSessionHasErrors('workflow');

        $this->move($payment, 'archived');
        $this->move($invoice, 'void')->assertSessionHasNoErrors();
    }

    public function test_approving_a_project_invoice_books_one_project_cost_and_void_archives_it(): void
    {
        $project = $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
        $invoice = $this->operatingRecord('supplier_invoices', 'draft', $this->invoice(['amount' => 1150, 'tax_amount' => 150, 'project_id' => $project]), 'Figma Oct');

        $this->move($invoice, 'approved')->assertSessionHasNoErrors();
        $costs = DB::table('operating_records')->where('type', 'costs')->get();
        $this->assertCount(1, $costs);
        $cost = json_decode($costs[0]->data, true);
        $this->assertSame(1000.0, (float) $cost['actual_cost']);
        $this->assertSame('tools', $cost['category']);
        $this->assertSame($project, $cost['project_id']);
        $this->assertTrue(DB::table('operating_links')->where('record_id', $costs[0]->id)->where('field', 'supplier_invoice_id')->where('target_id', $invoice)->exists());

        $this->move($invoice, 'void')->assertSessionHasNoErrors();
        $this->assertSame('archived', $this->stateOf($costs[0]->id));
    }

    public function test_only_finance_and_owner_see_supplier_invoices(): void
    {
        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/supplier_invoices')->assertForbidden();
        $this->actingAs($this->finance)->get('/admin/operations/supplier_invoices')->assertOk();
    }
}
