<?php

namespace Tests\Feature\Operating;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AssetMovementsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private User $hr;

    private int $asset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hr = $this->operatingUser('hr');
        $this->asset = $this->operatingRecord('assets', 'draft', ['category' => 'laptop', 'asset_tag' => 'AST-0001', 'serial_number' => 'C02XYZ', 'condition' => 'new'], 'MacBook Pro 14');
    }

    /**
     * Records a movement as draft and completes it, returning the completion response.
     *
     * @param  array<string, mixed>  $data
     */
    private function movement(string $kind, array $data = []): TestResponse
    {
        $this->actingAs($this->hr)->post('/admin/operations/asset_movements', ['title' => ucfirst($kind), 'reason' => 'Custody', 'data' => array_merge(['asset_id' => $this->asset, 'movement' => $kind, 'movement_date' => '2026-10-12'], $data)])->assertSessionHasNoErrors();
        $id = DB::table('operating_records')->where('type', 'asset_movements')->max('id');

        return $this->actingAs($this->hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'completed', 'version' => 1, 'reason' => 'Signed handover']);
    }

    private function asset(): object
    {
        return DB::table('operating_records')->find($this->asset);
    }

    public function test_assigning_then_transferring_moves_custody_and_notifies_the_receiver(): void
    {
        $first = $this->operatingUser('developer');
        $firstEmployee = $this->employeeFor($first);
        $second = $this->operatingUser('designer');
        $secondEmployee = $this->employeeFor($second);

        $this->movement('assign', ['to_employee_id' => $firstEmployee, 'condition' => 'good'])->assertSessionHasNoErrors();
        $this->assertSame($firstEmployee, $this->recordData($this->asset)['assignee_id']);
        $this->assertSame('good', $this->recordData($this->asset)['condition']);
        $this->assertSame('active', $this->asset()->status);
        $this->assertSame(1, $first->notifications()->count());

        $this->movement('transfer', ['to_employee_id' => $secondEmployee])->assertSessionHasNoErrors();
        $this->assertSame($secondEmployee, $this->recordData($this->asset)['assignee_id']);
        $this->assertSame($firstEmployee, $this->recordData(DB::table('operating_records')->where('type', 'asset_movements')->max('id'))['from_employee_id']);
        $this->assertSame(1, $second->notifications()->count());
        $this->assertSame(3, $this->asset()->version);

        $this->movement('return')->assertSessionHasNoErrors();
        $this->assertNull($this->recordData($this->asset)['assignee_id']);
    }

    public function test_invalid_movements_are_rejected(): void
    {
        $employee = $this->employeeFor($this->operatingUser('developer'));

        $this->movement('transfer', ['to_employee_id' => $employee])->assertSessionHasErrors('workflow');
        $this->movement('return')->assertSessionHasErrors('workflow');
        $this->movement('maintenance_done')->assertSessionHasErrors('workflow');
        $this->movement('assign', ['to_employee_id' => $employee]);
        $this->movement('assign', ['to_employee_id' => $employee])->assertSessionHasErrors('workflow');
        $this->movement('transfer', ['to_employee_id' => $employee])->assertSessionHasErrors('workflow');
        $this->actingAs($this->hr)->post('/admin/operations/asset_movements', ['title' => 'No one', 'reason' => 'Custody', 'data' => ['asset_id' => $this->asset, 'movement' => 'assign', 'movement_date' => '2026-10-12']])->assertSessionHasErrors('workflow');
    }

    public function test_maintenance_round_trip_restores_the_asset(): void
    {
        $vendor = $this->operatingRecord('suppliers', 'active', ['category' => 'services'], 'Repair shop');
        $this->movement('assign', ['to_employee_id' => $this->employeeFor($this->operatingUser('developer'))]);

        $this->movement('maintenance', ['condition' => 'damaged', 'supplier_id' => $vendor])->assertSessionHasNoErrors();
        $this->assertSame('maintenance', $this->asset()->status);
        $this->movement('transfer', ['to_employee_id' => $this->employeeFor($this->operatingUser('qa'))])->assertSessionHasErrors('workflow');

        $this->movement('maintenance_done', ['condition' => 'good', 'cost' => 120, 'currency' => 'USD', 'supplier_id' => $vendor])->assertSessionHasNoErrors();
        $this->assertSame('active', $this->asset()->status);
        $this->assertSame('good', $this->recordData($this->asset)['condition']);
        $this->assertArrayNotHasKey('status_before_maintenance', $this->recordData($this->asset));
    }

    public function test_completed_movements_are_permanent(): void
    {
        $this->movement('assign', ['to_employee_id' => $this->employeeFor($this->operatingUser('developer'))]);
        $id = DB::table('operating_records')->where('type', 'asset_movements')->max('id');

        $this->actingAs($this->hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'archived', 'version' => 2, 'reason' => 'Undo'])->assertSessionHasErrors('workflow');
        $this->actingAs($this->hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'draft', 'version' => 2, 'reason' => 'Undo'])->assertSessionHasErrors('workflow');
    }

    public function test_create_form_prefills_the_movement_kind(): void
    {
        $this->actingAs($this->hr)->get("/admin/operations/asset_movements/create?prefill[asset_id]={$this->asset}&prefill[movement]=return")
            ->assertOk()->assertSee('<option value="return" selected', false)->assertSee('<option value="'.$this->asset.'" selected', false);
        $this->actingAs($this->hr)->get('/admin/operations/asset_movements/create?prefill[movement]=steal')->assertOk()->assertDontSee('selected>', false);
    }
}
