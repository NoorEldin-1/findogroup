<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AssetsRegisterTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function asset(array $data = []): array
    {
        return ['title' => 'MacBook Pro 14', 'reason' => 'Register', 'data' => array_merge(['category' => 'laptop', 'serial_number' => 'C02XYZ', 'purchase_date' => '2026-01-10', 'cost' => 2200, 'currency' => 'USD', 'warranty_end' => '2027-01-10'], $data)];
    }

    public function test_assets_get_a_tag_and_serials_are_unique(): void
    {
        $hr = $this->operatingUser('hr');
        $this->actingAs($hr)->post('/admin/operations/assets', $this->asset())->assertSessionHasNoErrors();
        $first = DB::table('operating_records')->where('type', 'assets')->max('id');
        $this->assertSame('AST-0001', $this->recordData($first)['asset_tag']);

        $this->actingAs($hr)->post('/admin/operations/assets', $this->asset(['serial_number' => ' c02xyz ']))->assertSessionHasErrors('workflow');
        $this->actingAs($hr)->post('/admin/operations/assets', $this->asset(['serial_number' => 'OTHER', 'asset_tag' => 'ast-0001']))->assertSessionHasErrors('workflow');
        $this->actingAs($hr)->post('/admin/operations/assets', $this->asset(['serial_number' => 'OTHER', 'warranty_end' => '2025-01-01']))->assertSessionHasErrors('workflow');
        $this->actingAs($hr)->post('/admin/operations/assets', $this->asset(['serial_number' => 'OTHER']))->assertSessionHasNoErrors();
        $this->assertSame('AST-0002', $this->recordData(DB::table('operating_records')->where('type', 'assets')->max('id'))['asset_tag']);
    }

    public function test_employees_see_only_their_own_custody_without_costs(): void
    {
        $developer = $this->operatingUser('developer');
        $employee = $this->employeeFor($developer);
        $this->operatingRecord('assets', 'active', $this->asset(['assignee_id' => $employee, 'asset_tag' => 'AST-0001'])['data'], 'My laptop');
        $this->operatingRecord('assets', 'active', $this->asset(['serial_number' => 'S2', 'asset_tag' => 'AST-0002'])['data'], 'Spare laptop');

        $rows = $this->actingAs($developer)->getJson('/admin/operations/assets?format=json')->assertOk()->json('data');
        $this->assertSame(['My laptop'], array_column($rows, 'title'));
        $this->assertSame('C02XYZ', $rows[0]['data']['serial_number']);
        $this->assertArrayNotHasKey('cost', $rows[0]['data']);
        $this->actingAs($developer)->post('/admin/operations/assets', $this->asset(['serial_number' => 'S3']))->assertForbidden();
        $this->actingAs($this->operatingUser('qa'))->getJson('/admin/operations/assets?format=json')->assertForbidden();
    }

    public function test_assets_cannot_enter_maintenance_by_hand_or_retire_while_assigned(): void
    {
        $owner = $this->operatingUser('owner');
        $assigned = $this->operatingRecord('assets', 'active', $this->asset(['assignee_id' => $this->employeeFor($this->operatingUser('developer'))])['data'], 'Assigned');
        $free = $this->operatingRecord('assets', 'active', $this->asset(['serial_number' => 'S2'])['data'], 'Free');
        $move = fn (int $id, string $target) => $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => $target, 'version' => DB::table('operating_records')->where('id', $id)->value('version'), 'reason' => 'Lifecycle']);

        $move($free, 'maintenance')->assertSessionHasErrors('workflow');
        $move($assigned, 'retired')->assertSessionHasErrors('workflow');
        $move($free, 'retired')->assertSessionHasNoErrors();
        $move($free, 'active')->assertSessionHasErrors('workflow');
    }

    public function test_asset_managers_keep_access(): void
    {
        $this->actingAs($this->operatingUser('support'))->post('/admin/operations/assets', $this->asset())->assertSessionHasNoErrors();
        $this->actingAs($this->operatingUser('finance'))->get('/admin/operations/assets')->assertOk()->assertSee('MacBook Pro 14');
    }
}
