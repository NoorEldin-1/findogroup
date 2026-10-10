<?php

namespace Tests\Feature\Operating;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class PurchaseOrdersTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private User $owner;

    private int $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00'));
        $this->owner = $this->operatingUser('owner');
        $this->supplier = $this->operatingRecord('suppliers', 'active', ['category' => 'hardware'], 'Dell store');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function order(array $data = []): array
    {
        return ['title' => 'Laptops order', 'reason' => 'Order', 'data' => array_merge(['supplier_id' => $this->supplier, 'description' => '2 x Latitude', 'amount' => 1000, 'currency' => 'USD', 'order_date' => '2026-10-12', 'expected_delivery' => '2026-10-20'], $data)];
    }

    private function move(int $id, string $target, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->owner)->post("/admin/operations/record/{$id}/transition", ['target' => $target, 'version' => DB::table('operating_records')->where('id', $id)->value('version'), 'reason' => 'Step']);
    }

    private function lastOrder(): int
    {
        return DB::table('operating_records')->where('type', 'purchase_orders')->max('id');
    }

    public function test_orders_get_sequential_numbers_and_follow_stages(): void
    {
        $finance = $this->operatingUser('finance');
        $this->actingAs($finance)->post('/admin/operations/purchase_orders', $this->order())->assertSessionHasNoErrors();
        $first = $this->lastOrder();
        $this->actingAs($finance)->post('/admin/operations/purchase_orders', $this->order())->assertSessionHasNoErrors();

        $this->assertSame('PO-2026-0001', $this->recordData($first)['po_number']);
        $this->assertSame('PO-2026-0002', $this->recordData($this->lastOrder())['po_number']);
        $this->move($first, 'approved')->assertSessionHasErrors('workflow');
        $this->move($first, 'submitted', $finance)->assertSessionHasNoErrors();
        $this->move($first, 'approved', $finance)->assertForbidden();
        $this->move($first, 'approved')->assertSessionHasNoErrors();
        $this->actingAs($finance)->post('/admin/operations/purchase_orders', $this->order(['expected_delivery' => '2026-10-01']))->assertSessionHasErrors('workflow');
    }

    public function test_an_order_from_a_request_needs_an_approved_request_and_marks_it_ordered(): void
    {
        $request = ['requester_id' => $this->employeeFor($this->operatingUser('delivery')), 'category' => 'equipment', 'description' => 'Laptops', 'estimated_amount' => 1000, 'currency' => 'USD'];
        $open = $this->operatingRecord('purchase_requests', 'requested', $request, 'Open request');
        $this->actingAs($this->owner)->post('/admin/operations/purchase_orders', $this->order(['purchase_request_id' => $open]))->assertSessionHasErrors('workflow');

        $approved = $this->operatingRecord('purchase_requests', 'approved', $request, 'Approved request');
        $this->actingAs($this->owner)->post('/admin/operations/purchase_orders', $this->order(['purchase_request_id' => $approved, 'currency' => 'EGP']))->assertSessionHasErrors('workflow');
        $this->actingAs($this->owner)->post('/admin/operations/purchase_orders', $this->order(['purchase_request_id' => $approved, 'amount' => 1200]))->assertSessionHasNoErrors();
        $tooHigh = $this->lastOrder();
        $this->move($tooHigh, 'submitted');
        $this->move($tooHigh, 'approved')->assertSessionHasErrors('workflow');

        $this->actingAs($this->owner)->post('/admin/operations/purchase_orders', $this->order(['purchase_request_id' => $approved, 'amount' => 1050]))->assertSessionHasNoErrors();
        $fits = $this->lastOrder();
        $this->move($fits, 'submitted');
        $this->move($fits, 'approved')->assertSessionHasNoErrors();
        $this->assertSame('ordered', DB::table('operating_records')->where('id', $approved)->value('status'));
        $this->assertTrue(DB::table('operating_events')->where('record_id', $approved)->where('action', 'transition')->exists());
    }

    public function test_after_approval_only_delivery_fields_change_and_receiving_needs_a_date(): void
    {
        $order = $this->operatingRecord('purchase_orders', 'approved', $this->order()['data'] + ['po_number' => 'PO-2026-0001'], 'Laptops order');
        $edit = fn (array $data) => $this->actingAs($this->owner)->post("/admin/operations/record/{$order}", ['title' => 'Laptops order', 'version' => DB::table('operating_records')->where('id', $order)->value('version'), 'reason' => 'Update', 'data' => array_merge($this->order()['data'], $data)]);

        $this->move($order, 'received')->assertSessionHasErrors('workflow');
        $edit(['amount' => 900])->assertSessionHasErrors('workflow');
        $edit(['delivered_at' => '2026-10-19', 'delivery_note' => 'Both units OK'])->assertSessionHasNoErrors();
        $this->move($order, 'received')->assertSessionHasNoErrors();
        $this->assertSame('PO-2026-0001', $this->recordData($order)['po_number']);
    }

    public function test_cancel_is_not_allowed_after_receipt(): void
    {
        $received = $this->operatingRecord('purchase_orders', 'received', $this->order()['data'] + ['delivered_at' => '2026-10-19'], 'Received');
        $approved = $this->operatingRecord('purchase_orders', 'approved', $this->order()['data'], 'Approved');

        $this->move($received, 'cancelled')->assertSessionHasErrors('workflow');
        $this->move($approved, 'cancelled')->assertSessionHasNoErrors();
    }
}
