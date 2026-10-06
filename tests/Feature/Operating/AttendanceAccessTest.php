<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceAccessTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_employee_sees_only_own_attendance_and_manager_sees_team(): void
    {
        $managerUser = $this->operatingUser('delivery');
        $managerId = $this->employeeFor($managerUser);
        $aliceUser = $this->operatingUser('developer');
        $aliceId = $this->employeeFor($aliceUser, ['manager_id' => $managerId]);
        $bobUser = $this->operatingUser('support');
        $bobId = $this->employeeFor($bobUser);
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $aliceId, 'work_date' => '2026-10-04'], 'Alice day');
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $bobId, 'work_date' => '2026-10-04'], 'Bob day');

        $this->actingAs($aliceUser)->getJson('/admin/operations/attendance?format=json')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Alice day');
        $this->actingAs($bobUser)->getJson('/admin/operations/attendance?format=json')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Bob day');
        $this->actingAs($managerUser)->getJson('/admin/operations/attendance?format=json')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Alice day');
    }

    public function test_only_hr_and_owner_can_write_attendance_or_holidays(): void
    {
        $managerUser = $this->operatingUser('delivery');
        $this->employeeFor($managerUser);

        $this->actingAs($managerUser)->get('/admin/operations/attendance/create')->assertForbidden();
        $this->actingAs($managerUser)->get('/admin/operations/holidays/create')->assertForbidden();
        $this->actingAs($this->operatingUser('hr'))->get('/admin/operations/attendance/create')->assertOk();
    }

    public function test_only_hr_or_owner_can_assign_a_direct_manager(): void
    {
        $managerUser = $this->operatingUser('delivery');
        $managerId = $this->employeeFor($managerUser);
        $staffId = $this->employeeFor($this->operatingUser('developer'));
        $payload = ['title' => 'Staff', 'reason' => 'Restructure', 'version' => 1, 'data' => ['job_title' => 'Developer', 'department' => 'delivery', 'manager_id' => $managerId]];

        $this->actingAs($managerUser)->post("/admin/operations/record/{$staffId}", $payload)->assertSessionHasErrors('workflow');
        $this->actingAs($managerUser)->post('/admin/operations/employees', ['title' => 'New', 'reason' => 'Hire', 'data' => ['job_title' => 'QA', 'department' => 'delivery', 'manager_id' => $managerId]])->assertSessionHasErrors('workflow');
        $this->actingAs($managerUser)->post("/admin/operations/record/{$staffId}", array_replace_recursive($payload, ['data' => ['manager_id' => null, 'job_title' => 'Senior developer']]))->assertSessionHasNoErrors();
        $this->assertNull($this->recordData($staffId)['manager_id']);

        $this->actingAs($this->operatingUser('hr'))->post("/admin/operations/record/{$staffId}", array_replace($payload, ['version' => 2]))->assertSessionHasNoErrors();
        $this->assertSame($managerId, (int) $this->recordData($staffId)['manager_id']);
    }

    public function test_shift_start_must_be_a_valid_time(): void
    {
        $hr = $this->operatingUser('hr');

        $this->actingAs($hr)->post('/admin/operations/employees', ['title' => 'New hire', 'reason' => 'Onboarding', 'data' => ['job_title' => 'QA', 'department' => 'delivery', 'shift_start' => '25:99']])->assertSessionHasErrors('data.shift_start');
        $this->actingAs($hr)->post('/admin/operations/employees', ['title' => 'New hire', 'reason' => 'Onboarding', 'data' => ['job_title' => 'QA', 'department' => 'delivery', 'shift_start' => '10:00']])->assertSessionHasNoErrors();
    }
}
