<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SuppliersRequestsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function request(int $requester): array
    {
        return ['title' => 'Two laptops', 'reason' => 'New hires', 'data' => ['requester_id' => $requester, 'category' => 'equipment', 'description' => 'Two developer laptops', 'estimated_amount' => 2400, 'currency' => 'USD']];
    }

    public function test_finance_creates_a_supplier_and_requesters_see_it_without_bank_details(): void
    {
        $finance = $this->operatingUser('finance');
        $delivery = $this->operatingUser('delivery');

        $this->actingAs($finance)->post('/admin/operations/suppliers', ['title' => 'Hostinger', 'reason' => 'New vendor', 'data' => ['category' => 'hosting', 'payment_terms' => 'net_30', 'bank_details' => 'IBAN EG00 1234', 'tax_number' => '123-456', 'agreement' => 'Yearly hosting plan']])->assertSessionHasNoErrors();
        $supplier = DB::table('operating_records')->where('type', 'suppliers')->value('id');
        $this->assertSame('net_30', $this->recordData($supplier)['payment_terms']);

        $seen = $this->actingAs($delivery)->getJson("/admin/operations/record/{$supplier}")->assertOk()->json('data');
        $this->assertSame('Yearly hosting plan', $seen['agreement']);
        $this->assertArrayNotHasKey('bank_details', $seen);
        $this->assertArrayNotHasKey('tax_number', $seen);
        $this->actingAs($delivery)->post('/admin/operations/suppliers', ['title' => 'X', 'reason' => 'Try', 'data' => ['category' => 'other']])->assertForbidden();
    }

    public function test_requesters_file_requests_for_themselves_and_see_only_theirs(): void
    {
        $delivery = $this->operatingUser('delivery');
        $mine = $this->employeeFor($delivery);
        $other = $this->employeeFor($this->operatingUser('developer'));

        $this->actingAs($delivery)->post('/admin/operations/purchase_requests', $this->request($other))->assertSessionHasErrors('workflow');
        $this->actingAs($delivery)->post('/admin/operations/purchase_requests', $this->request($mine))->assertSessionHasNoErrors();
        $this->operatingRecord('purchase_requests', 'requested', $this->request($other)['data'], 'Someone else', $this->operatingUser('owner')->id);

        $titles = collect($this->actingAs($delivery)->getJson('/admin/operations/purchase_requests?format=json')->json('data'))->pluck('title')->all();
        $this->assertSame(['Two laptops'], $titles);
    }

    public function test_only_the_owner_approves_or_rejects_and_ordered_is_automatic(): void
    {
        $delivery = $this->operatingUser('delivery');
        $request = $this->operatingRecord('purchase_requests', 'requested', $this->request($this->employeeFor($delivery))['data'], 'Two laptops', $delivery->id);
        $move = fn ($user, string $target) => $this->actingAs($user)->post("/admin/operations/record/{$request}/transition", ['target' => $target, 'version' => DB::table('operating_records')->where('id', $request)->value('version'), 'reason' => 'Decision']);

        $move($delivery, 'approved')->assertForbidden();
        $move($delivery, 'rejected')->assertForbidden();
        $owner = $this->operatingUser('owner');
        $move($owner, 'ordered')->assertSessionHasErrors('workflow');
        $move($owner, 'approved')->assertSessionHasNoErrors();
        $this->assertSame('approved', DB::table('operating_records')->where('id', $request)->value('status'));
        $move($owner, 'rejected')->assertSessionHasErrors('workflow');
    }

    public function test_roles_outside_procurement_cannot_see_it(): void
    {
        $this->actingAs($this->operatingUser('developer'))->get('/admin/operations/suppliers')->assertForbidden();
        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/suppliers')->assertOk();
    }
}
