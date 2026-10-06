<?php

namespace Tests\Feature\Operating;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_on_time_check_in_earns_green_point_with_server_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 08:58:40'));
        $user = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($user);

        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'remote', 'check_in' => '2026-10-04T07:00'])->assertSessionHasNoErrors();

        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $data = json_decode($record->data, true);
        $this->assertSame('open', $record->status);
        $this->assertSame('2026-10-04T08:58', $data['check_in']);
        $this->assertSame('green', $data['point']);
        $this->assertSame('remote', $data['mode']);
        $this->assertSame($employeeId, $data['assignee_id']);
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_late_check_in_is_black_and_notifies_employee(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 09:12'));
        $user = $this->operatingUser('developer');
        $this->employeeFor($user);

        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'office']);

        $data = json_decode(DB::table('operating_records')->where('type', 'attendance')->value('data'), true);
        $this->assertSame('black', $data['point']);
        $this->assertSame(12, $data['late_minutes']);
        $this->assertSame('late_check_in', $user->notifications()->sole()->data['kind']);
    }

    public function test_second_check_in_is_rejected_and_check_out_closes_the_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 09:00'));
        $user = $this->operatingUser('qa');
        $this->employeeFor($user);
        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'office']);

        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'office'])->assertSessionHasErrors('workflow');

        $this->travelTo(CarbonImmutable::parse('2026-10-04 16:30'));
        $this->actingAs($user)->post('/admin/operations/attendance/check-out')->assertSessionHasNoErrors();
        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $data = json_decode($record->data, true);
        $this->assertSame('closed', $record->status);
        $this->assertSame(450, $data['worked_minutes']);
        $this->assertSame(30, $data['early_leave_minutes']);
        $this->assertSame(['check_in', 'check_out'], DB::table('operating_events')->where('record_id', $record->id)->orderBy('id')->pluck('action')->all());
    }

    public function test_check_out_without_check_in_fails_and_unlinked_user_is_forbidden(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 17:00'));
        $user = $this->operatingUser('developer');
        $this->employeeFor($user);

        $this->actingAs($user)->post('/admin/operations/attendance/check-out')->assertSessionHasErrors('workflow');
        $this->actingAs($this->operatingUser('developer'))->post('/admin/operations/attendance/check-in', ['mode' => 'office'])->assertForbidden();
    }
}
