<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class TaskPriorityTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function project(): int
    {
        return $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    public function test_task_priority_accepts_only_the_fixed_list(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $task = ['title' => 'Login', 'reason' => 'Plan', 'data' => ['project_id' => $this->project(), 'priority' => 'urgent']];

        $this->actingAs($lead)->post('/admin/operations/tasks', $task)->assertSessionHasErrors('data.priority');
        $task['data']['priority'] = 'high';
        $this->actingAs($lead)->post('/admin/operations/tasks', $task)->assertSessionHasNoErrors();
    }

    public function test_legacy_priorities_are_normalized_once_with_an_audit_trail(): void
    {
        $project = $this->project();
        $high = $this->operatingRecord('tasks', 'backlog', ['project_id' => $project, 'priority' => ' High '], 'A');
        $urgent = $this->operatingRecord('tasks', 'done', ['project_id' => $project, 'priority' => 'عاجل'], 'B');
        $unknown = $this->operatingRecord('tasks', 'ready', ['project_id' => $project, 'priority' => 'ASAP-ish'], 'C');
        $empty = $this->operatingRecord('tasks', 'backlog', ['project_id' => $project, 'priority' => null], 'D');
        $valid = $this->operatingRecord('tasks', 'backlog', ['project_id' => $project, 'priority' => 'low'], 'E');

        $this->artisan('operating:normalize-task-priorities --dry-run')->expectsOutputToContain('3 tasks normalized (dry run).')->assertSuccessful();
        $this->assertSame(' High ', $this->recordData($high)['priority']);

        $this->artisan('operating:normalize-task-priorities')->expectsOutputToContain('3 tasks normalized.')->assertSuccessful();
        $this->assertSame('high', $this->recordData($high)['priority']);
        $this->assertSame('critical', $this->recordData($urgent)['priority']);
        $this->assertSame('medium', $this->recordData($unknown)['priority']);
        $this->assertSame('ASAP-ish', $this->recordData($unknown)['priority_note']);
        $this->assertNull($this->recordData($empty)['priority']);
        $this->assertSame(1, DB::table('operating_records')->where('id', $valid)->value('version'));
        $this->assertSame(2, DB::table('operating_records')->where('id', $high)->value('version'));
        $this->assertTrue(DB::table('operating_events')->where('record_id', $high)->where('action', 'update')->exists());

        $this->artisan('operating:normalize-task-priorities')->expectsOutputToContain('0 tasks normalized.')->assertSuccessful();
    }
}
