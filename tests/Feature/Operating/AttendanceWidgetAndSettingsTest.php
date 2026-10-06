<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceWidgetAndSettingsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_employee_sees_check_in_button_and_two_day_warning(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06 08:30'));
        $user = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($user);
        foreach (['2026-10-04', '2026-10-05'] as $day) {
            $this->operatingRecord('attendance', 'absent', ['assignee_id' => $employeeId, 'work_date' => $day, 'point' => 'red'], 'Absent');
        }

        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('/admin/operations/attendance/check-in', false)->assertSee('لديك 2 أيام عمل متتالية بدون تسجيل حضور');
    }

    public function test_warning_counts_today_once_the_day_is_closed_as_absent(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06 23:58'));
        $user = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($user);
        foreach (['2026-10-05', '2026-10-06'] as $day) {
            $this->operatingRecord('attendance', 'absent', ['assignee_id' => $employeeId, 'work_date' => $day, 'point' => 'red'], 'Absent');
        }

        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('لديك 2 أيام عمل متتالية بدون تسجيل حضور')->assertDontSee('/admin/operations/attendance/check-in', false);
    }

    public function test_owner_updates_attendance_rules_with_version_and_audit(): void
    {
        $owner = $this->operatingUser('owner');
        $payload = ['version' => 0, 'work_start' => '10:00', 'work_end' => '18:00', 'work_days' => ['0', '1', '2', '3', '4'], 'grace_minutes' => 5, 'bonus_min' => 25, 'bonus_max' => 40, 'bonus_eligibility_percent' => 90, 'absence_alert_days' => 2, 'effective_date' => '2026-10-01', 'reason' => 'Management decision'];
        $this->travelTo(CarbonImmutable::parse('2026-10-06 10:00'));

        $this->actingAs($this->operatingUser('hr'))->post('/admin/operations/settings/attendance', $payload)->assertForbidden();
        $this->actingAs($owner)->post('/admin/operations/settings/attendance', $payload)->assertSessionHasNoErrors();

        $rules = app(OperatingAttendance::class)->rules();
        $this->assertSame('10:00', $rules['work_start']);
        $this->assertSame([0, 1, 2, 3, 4], $rules['work_days']);
        $this->assertSame(5, $rules['grace_minutes']);
        $this->assertDatabaseHas('operating_events', ['action' => 'configure_attendance']);
        $this->actingAs($owner)->post('/admin/operations/settings/attendance', $payload)->assertSessionHasErrors('workflow');

        $this->actingAs($owner)->post('/admin/operations/settings/attendance', array_merge($payload, ['version' => 1, 'work_start' => '11:00', 'effective_date' => '2026-12-01']))->assertSessionHasNoErrors();
        $this->assertSame('10:00', app(OperatingAttendance::class)->rules()['work_start']);
        $this->actingAs($owner)->get('/admin/operations/settings')->assertOk()->assertSee('2026-12-01');
    }
}
