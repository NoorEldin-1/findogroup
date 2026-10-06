<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceMonthlyReportTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_monthly_report_lists_eligible_employees_for_hr(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeId = $this->employeeFor($this->operatingUser('developer'));
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $employeeId, 'work_date' => '2026-09-14', 'point' => 'green', 'late_minutes' => 0, 'worked_minutes' => 480], 'Day');

        $this->artisan('operating:attendance-monthly-report', ['--month' => '2026-09'])->assertSuccessful();

        $data = $hr->notifications()->sole()->data;
        $this->assertSame('bonus_report', $data['kind']);
        $this->assertStringContainsString('2026-09', $data['url']);
        $this->assertStringContainsString('40', $data['body']['en']);
        $this->artisan('operating:attendance-monthly-report', ['--month' => '2026-13'])->assertFailed();
    }
}
