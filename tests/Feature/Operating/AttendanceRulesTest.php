<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceRulesTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $employee
     * @return array<string, mixed>
     */
    private function derived(array $data, string $status = 'closed', array $employee = []): array
    {
        $employeeId = $this->employeeFor($this->operatingUser('developer'), $employee);

        return app(OperatingAttendance::class)->derive(array_merge(['assignee_id' => $employeeId, 'work_date' => '2026-10-04', 'check_in' => null, 'check_out' => null], $data), $status);
    }

    public function test_check_in_at_start_time_is_green(): void
    {
        $data = $this->derived(['check_in' => '2026-10-04T09:00', 'check_out' => '2026-10-04T17:00']);

        $this->assertSame('green', $data['point']);
        $this->assertSame(0, $data['late_minutes']);
        $this->assertSame(480, $data['worked_minutes']);
    }

    public function test_late_check_in_is_black_with_minutes_and_early_leave(): void
    {
        $data = $this->derived(['check_in' => '2026-10-04T09:17', 'check_out' => '2026-10-04T16:30']);

        $this->assertSame('black', $data['point']);
        $this->assertSame(17, $data['late_minutes']);
        $this->assertSame(30, $data['early_leave_minutes']);
        $this->assertSame(0, $data['overtime_minutes']);
    }

    public function test_missing_check_in_is_red_and_excused_has_no_point(): void
    {
        $this->assertSame('red', $this->derived([], 'absent')['point']);
        $this->assertNull($this->derived([], 'excused')['point']);
    }

    public function test_employee_shift_overrides_company_start_and_counts_overtime(): void
    {
        $data = $this->derived(['check_in' => '2026-10-04T09:55', 'check_out' => '2026-10-04T19:00'], 'closed', ['shift_start' => '10:00', 'shift_end' => '18:00']);

        $this->assertSame('green', $data['point']);
        $this->assertSame(60, $data['overtime_minutes']);
    }

    public function test_friday_and_holidays_are_not_workdays_and_carry_no_point(): void
    {
        $attendance = app(OperatingAttendance::class);
        $this->operatingRecord('holidays', 'active', ['holiday_date' => '2026-10-06'], 'National day');

        $this->assertFalse($attendance->isWorkday(CarbonImmutable::parse('2026-10-02')));
        $this->assertFalse($attendance->isWorkday(CarbonImmutable::parse('2026-10-06')));
        $this->assertTrue($attendance->isWorkday(CarbonImmutable::parse('2026-10-04')));
        $this->assertNull($this->derived(['work_date' => '2026-10-02', 'check_in' => '2026-10-02T11:00', 'check_out' => '2026-10-02T13:00'])['point']);
    }

    public function test_bonus_scales_from_25_to_40_above_eligibility(): void
    {
        $attendance = app(OperatingAttendance::class);

        $this->assertSame(0.0, $attendance->bonus(79.99));
        $this->assertSame(25.0, $attendance->bonus(80));
        $this->assertSame(32.5, $attendance->bonus(90));
        $this->assertSame(40.0, $attendance->bonus(100));
    }
}
