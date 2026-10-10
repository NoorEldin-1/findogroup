<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAssets;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AssetsOverviewTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 09:00'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function asset(string $title, string $status = 'active', array $data = []): int
    {
        return $this->operatingRecord('assets', $status, array_merge(['category' => 'laptop', 'asset_tag' => 'AST-'.$title], $data), $title);
    }

    public function test_overview_summarises_custody_maintenance_warranty_and_low_stock(): void
    {
        $employee = $this->employeeFor($this->operatingUser('developer'));
        $this->asset('Laptop A', 'active', ['assignee_id' => $employee, 'warranty_end' => '2026-10-30']);
        $this->asset('Laptop B', 'active', ['assignee_id' => $employee]);
        $this->asset('Monitor', 'active', ['category' => 'monitor', 'warranty_end' => '2027-05-01']);
        $this->asset('Router', 'maintenance', ['category' => 'network']);
        $this->asset('Old phone', 'retired', ['category' => 'phone']);
        $this->operatingRecord('inventory_items', 'active', ['category' => 'office_supply', 'unit' => 'ream', 'min_quantity' => 5, 'quantity' => 2], 'A4 paper');
        $this->operatingRecord('inventory_items', 'active', ['category' => 'consumable', 'unit' => 'box', 'min_quantity' => 1, 'quantity' => 9], 'Coffee');

        $overview = app(OperatingAssets::class)->overview($this->operatingUser('hr'));

        $this->assertSame(['assets' => 4, 'assigned' => 2, 'available' => 1, 'maintenance' => 1, 'warranty' => 1, 'items' => 2, 'low_stock' => 1], $overview['counts']);
        $this->assertSame(2, $overview['custody'][0]['count']);
        $this->assertSame(['Laptop A'], array_column($overview['warranty'], 'title'));
        $this->assertSame(['A4 paper'], array_column($overview['low_stock'], 'title'));
        $this->assertSame(2, $overview['by_category']['laptop'] ?? null);

        $this->actingAs($this->operatingUser('finance'))->get('/admin/operations/assets/overview')->assertOk()->assertSee('A4 paper')->assertSee('Router');
    }

    public function test_asset_page_shows_custody_history_and_only_valid_actions(): void
    {
        $hr = $this->operatingUser('hr');
        $developer = $this->operatingUser('developer');
        $employee = $this->employeeFor($developer, ['job_title' => 'Developer']);
        $asset = $this->asset('Laptop A', 'active', ['assignee_id' => $employee]);
        $this->operatingRecord('asset_movements', 'completed', ['asset_id' => $asset, 'movement' => 'assign', 'to_employee_id' => $employee, 'movement_date' => '2026-10-01'], 'Handover');

        $page = $this->actingAs($hr)->get("/admin/operations/record/{$asset}")->assertOk();
        $page->assertSee('prefill[movement]=transfer', false)->assertSee('prefill[movement]=return', false)->assertDontSee('prefill[movement]=assign', false)->assertSee('2026-10-01');

        $this->actingAs($developer)->get("/admin/operations/record/{$asset}")->assertOk()->assertDontSee('prefill[movement]=', false)->assertDontSee('2026-10-01');
    }

    public function test_asset_alerts_cover_warranty_and_low_stock_once(): void
    {
        $owner = $this->operatingUser('owner');
        $this->asset('Laptop A', 'active', ['warranty_end' => '2026-10-30']);
        $this->asset('Laptop B', 'retired', ['warranty_end' => '2026-10-30']);
        $this->operatingRecord('inventory_items', 'active', ['category' => 'office_supply', 'unit' => 'ream', 'min_quantity' => 5, 'quantity' => 2], 'A4 paper');

        $this->artisan('operating:asset-alerts')->expectsOutputToContain('1 low stock, 1 warranty.')->assertSuccessful();
        $this->artisan('operating:asset-alerts')->expectsOutputToContain('0 low stock, 0 warranty.')->assertSuccessful();
        $this->assertSame(2, $owner->notifications()->count());
    }

    public function test_employees_without_asset_management_cannot_open_the_overview(): void
    {
        $developer = $this->operatingUser('developer');
        $this->employeeFor($developer);

        $this->actingAs($developer)->get('/admin/operations/assets/overview')->assertForbidden();
        $this->actingAs($developer)->get('/admin/operations/assets')->assertOk();
    }
}
