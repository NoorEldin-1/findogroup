<?php

namespace Tests\Feature\Operating;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private User $hr;

    private int $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hr = $this->operatingUser('hr');
        $this->actingAs($this->hr)->post('/admin/operations/inventory_items', ['title' => 'A4 paper', 'reason' => 'New item', 'data' => ['sku' => 'PAP-A4', 'category' => 'office_supply', 'unit' => 'ream', 'min_quantity' => 5]])->assertSessionHasNoErrors();
        $this->item = DB::table('operating_records')->where('type', 'inventory_items')->max('id');
    }

    private function stock(string $kind, float $quantity): TestResponse
    {
        $this->actingAs($this->hr)->post('/admin/operations/stock_movements', ['title' => ucfirst($kind).' '.$quantity, 'reason' => 'Stock', 'data' => ['item_id' => $this->item, 'movement' => $kind, 'quantity' => $quantity, 'movement_date' => '2026-10-12']])->assertSessionHasNoErrors();
        $id = DB::table('operating_records')->where('type', 'stock_movements')->max('id');

        return $this->actingAs($this->hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'completed', 'version' => 1, 'reason' => 'Counted']);
    }

    private function quantity(): float
    {
        return (float) $this->recordData($this->item)['quantity'];
    }

    public function test_stock_movements_update_quantity_and_never_go_negative(): void
    {
        $this->assertSame(0.0, $this->quantity());
        $this->stock('in', 10)->assertSessionHasNoErrors();
        $this->stock('out', 4)->assertSessionHasNoErrors();
        $this->assertSame(6.0, $this->quantity());
        $this->stock('out', 7)->assertSessionHasErrors('workflow');
        $this->assertSame(6.0, $this->quantity());
        $this->stock('adjust', 9)->assertSessionHasNoErrors();
        $this->assertSame(9.0, $this->quantity());

        $last = DB::table('operating_records')->where('type', 'stock_movements')->where('status', 'completed')->max('id');
        $this->assertSame(['before' => 6.0, 'after' => 9.0], ['before' => (float) $this->recordData($last)['quantity_before'], 'after' => (float) $this->recordData($last)['quantity_after']]);
    }

    public function test_low_stock_alerts_once_per_drop_and_rearms_after_restock(): void
    {
        $owner = $this->operatingUser('owner');
        $this->stock('in', 10);
        $this->stock('out', 5);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $this->hr->notifications()->count());
        $this->stock('out', 1);
        $this->assertSame(1, $owner->notifications()->count());

        $this->stock('in', 20);
        $this->assertSame('', $this->recordData($this->item)['low_stock_alerted']);
        $this->stock('out', 20);
        $this->assertSame(2, $owner->notifications()->count());
        $this->actingAs($owner)->get("/admin/operations/record/{$this->item}")->assertSee(__('المخزون عند حد إعادة الطلب أو أقل. اطلب كمية جديدة.'));
    }

    public function test_skus_are_unique_and_quantity_is_not_editable(): void
    {
        $this->actingAs($this->hr)->post('/admin/operations/inventory_items', ['title' => 'Copy', 'reason' => 'New item', 'data' => ['sku' => 'pap-a4', 'category' => 'office_supply', 'unit' => 'ream', 'min_quantity' => 1]])->assertSessionHasErrors('workflow');
        $this->stock('in', 3);

        $this->actingAs($this->hr)->post("/admin/operations/record/{$this->item}", ['title' => 'A4 paper', 'version' => DB::table('operating_records')->where('id', $this->item)->value('version'), 'reason' => 'Edit', 'data' => ['sku' => 'PAP-A4', 'category' => 'office_supply', 'unit' => 'ream', 'min_quantity' => 2, 'quantity' => 999]])->assertSessionHasNoErrors();
        $this->assertSame(3.0, $this->quantity());
        $this->assertSame(2, (int) $this->recordData($this->item)['min_quantity']);
    }
}
