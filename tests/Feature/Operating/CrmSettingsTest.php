<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmSettingsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_defaults_apply_before_anything_is_saved(): void
    {
        $this->assertSame(['sla_enabled' => true, 'sla_hours' => 24, 'round_robin_enabled' => false, 'round_robin_pool' => []], app(OperatingCrm::class)->settings());
    }

    public function test_a_sales_manager_saves_settings_with_an_audited_reason(): void
    {
        $rep = $this->employeeFor($this->operatingUser('sales'));
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->get('/admin/operations/crm/settings')->assertOk()->assertSee(__('مهلة التواصل (SLA)'));
        $this->actingAs($manager)->post('/admin/operations/crm/settings', ['version' => 0, 'sla_enabled' => '1', 'sla_hours' => 4, 'round_robin_enabled' => '1', 'round_robin_pool' => [$rep], 'reason' => 'Faster response'])->assertSessionHasNoErrors();

        $settings = app(OperatingCrm::class)->settings();
        $this->assertSame(4, $settings['sla_hours']);
        $this->assertSame([$rep], $settings['round_robin_pool']);
        $this->assertTrue(DB::table('operating_events')->where('action', 'configure_crm')->where('reason', 'Faster response')->exists());
    }

    public function test_a_stale_version_is_rejected(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $payload = ['version' => 0, 'sla_enabled' => '1', 'sla_hours' => 8, 'reason' => 'First'];
        $this->actingAs($manager)->post('/admin/operations/crm/settings', $payload)->assertSessionHasNoErrors();

        $this->actingAs($manager)->post('/admin/operations/crm/settings', $payload)->assertSessionHasErrors('workflow');
    }

    public function test_the_pool_only_accepts_active_employees_and_hours_are_bounded(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $client = $this->operatingRecord('clients', 'active', ['company' => 'X', 'decision_maker' => 'Y', 'contact' => 'Z'], 'X');

        $this->actingAs($manager)->post('/admin/operations/crm/settings', ['version' => 0, 'sla_hours' => 900, 'reason' => 'Bad'])->assertSessionHasErrors('sla_hours');
        $this->actingAs($manager)->post('/admin/operations/crm/settings', ['version' => 0, 'sla_hours' => 24, 'round_robin_pool' => [$client], 'reason' => 'Bad'])->assertSessionHasErrors('workflow');
    }

    public function test_reps_and_other_roles_cannot_open_settings(): void
    {
        $this->actingAs($this->operatingUser('sales'))->get('/admin/operations/crm/settings')->assertForbidden();
        $this->actingAs($this->operatingUser('marketing'))->post('/admin/operations/crm/settings', ['version' => 0, 'sla_hours' => 4, 'reason' => 'x'])->assertForbidden();
    }
}
