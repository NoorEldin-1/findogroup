<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmAssignmentTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function lead(string $phone): array
    {
        return ['title' => 'Lead '.$phone, 'reason' => 'Inbound', 'data' => ['lead_phone' => $phone, 'lead_source' => 'facebook']];
    }

    private function enableRotation(array $pool): void
    {
        DB::table('operating_settings')->insert(['key' => 'crm', 'data' => json_encode(['round_robin_enabled' => true, 'round_robin_pool' => $pool]), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function assigneeOf(string $phone): ?int
    {
        return json_decode(DB::table('operating_records')->where('title', 'Lead '.$phone)->value('data'), true)['assignee_id'] ?? null;
    }

    public function test_manager_leads_rotate_across_the_pool_and_skip_inactive_employees(): void
    {
        $first = $this->employeeFor($this->operatingUser('sales'));
        $inactive = $this->employeeFor($this->operatingUser('sales'), [], 'archived');
        $second = $this->employeeFor($this->operatingUser('sales'));
        $this->enableRotation([$first, $inactive, $second]);
        $manager = $this->operatingUser('sales_manager');

        foreach (['01000000001', '01000000002', '01000000003'] as $phone) {
            $this->actingAs($manager)->post('/admin/operations/opportunities', $this->lead($phone))->assertSessionHasNoErrors();
        }

        $this->assertSame([$first, $second, $first], [$this->assigneeOf('01000000001'), $this->assigneeOf('01000000002'), $this->assigneeOf('01000000003')]);
    }

    public function test_a_rep_owns_the_lead_they_create_even_when_rotation_is_on(): void
    {
        $other = $this->employeeFor($this->operatingUser('sales'));
        $this->enableRotation([$other]);
        $rep = $this->operatingUser('sales');
        $own = $this->employeeFor($rep);

        $this->actingAs($rep)->post('/admin/operations/opportunities', $this->lead('01000000009'))->assertSessionHasNoErrors();

        $this->assertSame($own, $this->assigneeOf('01000000009'));
    }

    public function test_without_rotation_a_manager_lead_stays_unassigned_and_an_explicit_owner_wins(): void
    {
        $rep = $this->employeeFor($this->operatingUser('sales'));
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->post('/admin/operations/opportunities', $this->lead('01000000011'));
        $this->actingAs($manager)->post('/admin/operations/opportunities', ['title' => 'Lead 01000000012', 'reason' => 'x', 'data' => ['lead_phone' => '01000000012', 'lead_source' => 'facebook', 'assignee_id' => $rep]]);

        $this->assertNull($this->assigneeOf('01000000011'));
        $this->assertSame($rep, $this->assigneeOf('01000000012'));
    }
}
