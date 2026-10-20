<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait BuildsOperatingRecords
{
    protected function operatingUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'active' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function employeeFor(User $user, array $data = [], string $status = 'active'): int
    {
        return $this->operatingRecord('employees', $status, array_merge([
            'job_title' => 'Developer',
            'department' => 'delivery',
            'manager_id' => null,
            'user_id' => $user->id,
        ], $data), $user->name);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function operatingRecord(string $type, string $status, array $data, string $title = 'Record', ?int $ownerId = null): int
    {
        $id = DB::table('operating_records')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'owner_id' => $ownerId,
            'created_by' => $ownerId,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ($data as $field => $value) {
            if (str_ends_with($field, '_id') && $field !== 'user_id' && is_int($value)) {
                DB::table('operating_links')->insert(['record_id' => $id, 'target_id' => $value, 'field' => $field]);
            }
        }

        return $id;
    }

    /**
     * Record a stage change the way OperatingWorkflow::transition() does, so stage-reach metrics can be tested without walking every gate.
     */
    protected function transitionEvent(int $recordId, string $status, string $at = '2026-10-15 10:00:00'): void
    {
        DB::table('operating_events')->insert(['record_id' => $recordId, 'user_id' => null, 'action' => 'transition', 'reason' => 'test', 'before' => null, 'after' => json_encode(['status' => $status]), 'created_at' => $at]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function recordData(int $id): array
    {
        return json_decode(DB::table('operating_records')->where('id', $id)->value('data'), true);
    }
}
