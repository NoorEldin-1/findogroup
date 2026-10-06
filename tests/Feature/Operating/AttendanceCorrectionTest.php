<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(int $employeeId, array $data = []): array
    {
        return ['title' => 'Correction', 'reason' => 'Forgot to check in, confirmed by manager', 'data' => array_merge(['assignee_id' => $employeeId, 'work_date' => '2026-10-04', 'check_in' => '2026-10-04T09:20', 'check_out' => '2026-10-04T17:00', 'mode' => 'office', 'source' => 'manual', 'note' => 'Badge issue'], $data)];
    }

    public function test_hr_manual_record_gets_derived_point_and_closed_status(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeId = $this->employeeFor($this->operatingUser('developer'));

        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId))->assertSessionHasNoErrors();

        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $this->assertSame('closed', $record->status);
        $this->assertSame('black', json_decode($record->data, true)['point']);
        $this->assertSame(20, json_decode($record->data, true)['late_minutes']);
    }

    public function test_duplicate_day_is_rejected(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeId = $this->employeeFor($this->operatingUser('developer'));
        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId));

        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId))->assertSessionHasErrors('workflow');
    }

    public function test_times_must_fit_the_work_date_and_editing_recomputes_status(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeId = $this->employeeFor($this->operatingUser('developer'));

        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId, ['check_in' => '2026-10-05T09:00']))->assertSessionHasErrors('workflow');
        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId, ['check_out' => '2026-10-04T08:00']))->assertSessionHasErrors('workflow');

        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId, ['check_in' => null, 'check_out' => null]))->assertSessionHasNoErrors();
        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $this->assertSame('absent', $record->status);
        $this->assertSame('red', json_decode($record->data, true)['point']);

        $this->actingAs($hr)->post("/admin/operations/record/{$record->id}", $this->payload($employeeId, ['check_in' => '2026-10-04T08:50']) + ['version' => 1])->assertSessionHasNoErrors();
        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $this->assertSame('closed', $record->status);
        $this->assertSame('green', json_decode($record->data, true)['point']);
    }

    public function test_excusing_an_absence_requires_hr_and_a_note_and_clears_the_point(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeUser = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($employeeUser);
        $id = $this->operatingRecord('attendance', 'absent', ['assignee_id' => $employeeId, 'work_date' => '2026-10-04', 'check_in' => null, 'check_out' => null, 'mode' => 'office', 'source' => 'system', 'note' => null, 'point' => 'red'], 'Absent day');

        $this->actingAs($employeeUser)->post("/admin/operations/record/{$id}/transition", ['target' => 'excused', 'version' => 1, 'reason' => 'Sick'])->assertForbidden();
        $this->actingAs($hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'excused', 'version' => 1, 'reason' => 'Sick'])->assertSessionHasErrors('workflow');

        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(array_merge($this->recordData($id), ['note' => 'Medical report attached']))]);
        $this->actingAs($hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'excused', 'version' => 1, 'reason' => 'Sick'])->assertSessionHasNoErrors();

        $this->assertSame('excused', DB::table('operating_records')->where('id', $id)->value('status'));
        $this->assertNull($this->recordData($id)['point']);
    }
}
