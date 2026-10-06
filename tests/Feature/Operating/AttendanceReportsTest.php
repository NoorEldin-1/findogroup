<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceReportsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function day(int $employeeId, string $date, string $status, ?string $point, int $late = 0): void
    {
        $this->operatingRecord('attendance', $status, ['assignee_id' => $employeeId, 'work_date' => $date, 'point' => $point, 'late_minutes' => $late, 'early_leave_minutes' => 0, 'overtime_minutes' => 0, 'worked_minutes' => $point === 'red' || $point === null ? 0 : 480, 'mode' => 'office'], 'Day '.$date);
    }

    public function test_month_summary_computes_commitment_and_bonus(): void
    {
        $hr = $this->operatingUser('hr');
        $punctual = $this->employeeFor($this->operatingUser('developer'));
        $mixed = $this->employeeFor($this->operatingUser('qa'));
        foreach (range(1, 10) as $index) {
            $date = sprintf('2026-10-%02d', $index + 10);
            $this->day($punctual, $date, 'closed', 'green');
            $this->day($mixed, $date, 'closed', $index <= 8 ? 'green' : 'black', $index <= 8 ? 0 : 15);
        }
        $this->day($mixed, '2026-10-25', 'excused', null);
        $this->day($mixed, '2026-11-01', 'absent', 'red');

        $rows = collect(app(OperatingAttendance::class)->monthSummary($hr, 2026, 10))->keyBy('employee_id');

        $this->assertSame(100.0, $rows[$punctual]['commitment']);
        $this->assertSame(40.0, $rows[$punctual]['bonus']);
        $this->assertSame(80.0, $rows[$mixed]['commitment']);
        $this->assertSame(25.0, $rows[$mixed]['bonus']);
        $this->assertSame(30, $rows[$mixed]['late_minutes']);
        $this->assertSame(1, $rows[$mixed]['excused']);
        $this->assertSame(0, $rows[$mixed]['red']);
    }

    public function test_board_is_for_hr_and_managers_only(): void
    {
        $hr = $this->operatingUser('hr');
        $developer = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($developer);
        $this->day($employeeId, '2026-10-04', 'closed', 'black', 12);

        $this->actingAs($hr)->get('/admin/operations/attendance/board?date=2026-10-04')->assertOk()->assertSee($developer->name);
        $this->actingAs($developer)->get('/admin/operations/attendance/board?date=2026-10-04')->assertForbidden();
    }

    public function test_manager_board_and_report_only_cover_direct_reports(): void
    {
        $managerUser = $this->operatingUser('delivery');
        $managerId = $this->employeeFor($managerUser);
        $teamUser = $this->operatingUser('developer');
        $teamId = $this->employeeFor($teamUser, ['manager_id' => $managerId]);
        $otherUser = $this->operatingUser('developer');
        $otherId = $this->employeeFor($otherUser);
        $this->day($teamId, '2026-10-04', 'closed', 'green');
        $this->day($otherId, '2026-10-04', 'closed', 'green');

        $this->actingAs($managerUser)->get('/admin/operations/attendance/board?date=2026-10-04')->assertOk()->assertSee($teamUser->name)->assertDontSee($otherUser->name);
        $this->actingAs($managerUser)->get('/admin/operations/attendance/monthly?month=2026-10')->assertOk()->assertSee($teamUser->name)->assertDontSee($otherUser->name);
    }

    public function test_monthly_report_exports_csv(): void
    {
        $hr = $this->operatingUser('hr');
        $developer = $this->operatingUser('developer');
        $this->day($this->employeeFor($developer), '2026-10-04', 'closed', 'green');

        $response = $this->actingAs($hr)->get('/admin/operations/attendance/monthly?month=2026-10&format=csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString($developer->name, $response->streamedContent());
    }
}
