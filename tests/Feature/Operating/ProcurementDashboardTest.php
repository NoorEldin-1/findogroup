<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingProcurement;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class ProcurementDashboardTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-20 09:00'));
        $this->supplier = $this->operatingRecord('suppliers', 'active', ['category' => 'hardware'], 'Dell store');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function order(string $status, array $data = []): int
    {
        return $this->operatingRecord('purchase_orders', $status, array_merge(['supplier_id' => $this->supplier, 'description' => 'Laptops', 'amount' => 2000, 'currency' => 'USD', 'order_date' => '2026-10-01', 'expected_delivery' => '2026-10-10', 'po_number' => 'PO-2026-0001'], $data), 'Laptops order');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function invoice(string $number, float $amount, string $due, array $data = []): int
    {
        return $this->operatingRecord('supplier_invoices', 'approved', array_merge(['supplier_id' => $this->supplier, 'invoice_number' => $number, 'invoice_date' => '2026-08-15', 'amount' => $amount, 'currency' => 'USD', 'due_date' => $due], $data), 'Dell '.$number);
    }

    public function test_dashboard_shows_approvals_deliveries_and_payables_aging(): void
    {
        $this->operatingRecord('purchase_requests', 'requested', ['requester_id' => $this->employeeFor($this->operatingUser('hr')), 'category' => 'office', 'description' => 'Chairs', 'estimated_amount' => 300, 'currency' => 'USD'], 'Office chairs');
        $this->order('submitted', ['po_number' => 'PO-2026-0002']);
        $this->order('approved');
        $old = $this->invoice('A-1', 500, '2026-09-01');
        $soon = $this->invoice('A-2', 200, '2026-10-25');
        $this->operatingRecord('supplier_payments', 'active', ['supplier_invoice_id' => $soon, 'amount' => 50, 'paid_at' => '2026-10-19', 'reference' => 'T1'], 'Payment');

        $board = app(OperatingProcurement::class)->dashboard($this->operatingUser('owner'));

        $this->assertSame(['Office chairs'], array_column($board['pending_requests'], 'title'));
        $this->assertSame(['PO-2026-0002'], array_column($board['pending_orders'], 'number'));
        $this->assertSame(['PO-2026-0001'], array_column($board['late_deliveries'], 'number'));
        $this->assertSame(['currency' => 'USD', 'outstanding' => 650.0, 'current' => 150.0, 'd1_30' => 0.0, 'd31_60' => 500.0, 'd61_90' => 0.0, 'd90_plus' => 0.0, 'due_7' => 150.0], $board['payables'][0]);
        $this->assertSame([$old, $soon], array_column($board['due_invoices'], 'id'));
        $this->assertSame(700.0, $board['top_suppliers'][0]['spend']);

        $this->actingAs($this->operatingUser('owner'))->get('/admin/operations/procurement')->assertOk()->assertSee('PO-2026-0001')->assertSee('650.00 USD');
        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/procurement')->assertOk()->assertDontSee('650.00 USD')->assertDontSee('PO-2026-0001');
        $this->actingAs($this->operatingUser('developer'))->get('/admin/operations/procurement')->assertForbidden();
    }

    public function test_record_panels_show_commitment_and_remaining(): void
    {
        $finance = $this->operatingUser('finance');
        $order = $this->order('received', ['delivered_at' => '2026-10-11']);
        $invoice = $this->invoice('B-9', 1200, '2026-11-01', ['purchase_order_id' => $order]);
        $this->operatingRecord('supplier_payments', 'active', ['supplier_invoice_id' => $invoice, 'amount' => 200, 'paid_at' => '2026-10-19', 'reference' => 'T2'], 'Payment');

        $this->actingAs($finance)->get("/admin/operations/record/{$order}")->assertOk()->assertSee('1,200.00 USD')->assertSee('800.00 USD')->assertSee(__('تم الاستلام'));
        $this->actingAs($finance)->get("/admin/operations/record/{$invoice}")->assertOk()->assertSee('1,000.00 USD')->assertSee('prefill[supplier_invoice_id]='.$invoice, false);
        $this->actingAs($finance)->get("/admin/operations/record/{$this->supplier}")->assertOk()->assertSee('1,000.00 USD');
    }

    public function test_procurement_alerts_are_sent_once_per_date(): void
    {
        $owner = $this->operatingUser('owner');
        $finance = $this->operatingUser('finance');
        $order = $this->order('approved');
        $this->order('approved', ['delivered_at' => '2026-10-09', 'po_number' => 'PO-2026-0003']);
        $this->invoice('C-1', 100, '2026-10-01');
        $this->invoice('C-2', 100, '2026-10-24');
        $this->invoice('C-3', 100, '2026-12-24');

        $this->artisan('operating:procurement-alerts')->expectsOutputToContain('1 late deliveries, 1 invoices due, 1 invoices overdue.')->assertSuccessful();
        $this->artisan('operating:procurement-alerts')->expectsOutputToContain('0 late deliveries, 0 invoices due, 0 invoices overdue.')->assertSuccessful();

        $this->assertSame(3, $owner->notifications()->count());
        $this->assertSame(3, $finance->notifications()->count());
        $this->assertSame('2026-10-10', $this->recordData($order)['delivery_alerted_for']);
        $this->assertSame(1, DB::table('operating_records')->where('id', $order)->value('version'));
    }
}
