<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceCloseDayTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function statusOf(int $employeeId, string $day): ?string
    {
        return DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $day)->value('status');
    }

    public function test_close_day_marks_red_for_missing_and_excused_for_approved_leave(): void
    {
        $present = $this->employeeFor($this->operatingUser('developer'));
        $missing = $this->employeeFor($this->operatingUser('developer'));
        $onLeave = $this->employeeFor($this->operatingUser('qa'));
        $proposed = $this->employeeFor($this->operatingUser('qa'), [], 'proposed');
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $present, 'work_date' => '2026-10-04', 'point' => 'green'], 'Present');
        $this->operatingRecord('leave', 'approved', ['assignee_id' => $onLeave, 'start_date' => '2026-10-04', 'end_date' => '2026-10-05'], 'Leave');

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-04'])->assertSuccessful();
        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-04'])->assertSuccessful();

        $this->assertSame('absent', $this->statusOf($missing, '2026-10-04'));
        $this->assertSame('red', json_decode(DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $missing)->value('data'), true)['point']);
        $this->assertSame('excused', $this->statusOf($onLeave, '2026-10-04'));
        $this->assertNull($this->statusOf($proposed, '2026-10-04'));
        $this->assertSame(1, DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $missing)->count());
    }

    public function test_weekend_and_malformed_dates_are_skipped(): void
    {
        $this->employeeFor($this->operatingUser('developer'));

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-02'])->assertSuccessful();
        $this->artisan('operating:attendance-close-day', ['--date' => '2026-13-45'])->assertFailed();

        $this->assertSame(0, DB::table('operating_records')->where('type', 'attendance')->count());
    }

    public function test_second_consecutive_missing_workday_alerts_hr_manager_and_employee(): void
    {
        $hr = $this->operatingUser('hr');
        $managerUser = $this->operatingUser('delivery');
        $managerId = $this->employeeFor($managerUser);
        $employeeUser = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($employeeUser, ['manager_id' => $managerId]);
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $managerId, 'work_date' => '2026-10-04', 'point' => 'green'], 'Manager present');
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $managerId, 'work_date' => '2026-10-05', 'point' => 'green'], 'Manager present');

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-04']);
        $this->assertSame(0, $employeeUser->notifications()->where('data->kind', 'absence_streak')->count());
        $this->assertSame(1, $managerUser->notifications()->where('data->kind', 'missing_check_in')->count());
        $this->assertSame(1, $hr->notifications()->where('data->kind', 'missing_check_in_digest')->count());

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-05']);

        foreach ([$hr, $managerUser, $employeeUser] as $user) {
            $this->assertSame(1, $user->notifications()->where('data->kind', 'absence_streak')->count());
        }
        $this->assertSame(2, DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('status', 'absent')->count());
    }
}
