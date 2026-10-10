<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SprintsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function project(string $status = 'execution'): int
    {
        return $this->operatingRecord('projects', $status, ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sprint(int $project, string $status = 'planned', string $title = 'Sprint 1', array $data = []): int
    {
        return $this->operatingRecord('sprints', $status, array_merge(['project_id' => $project, 'goal' => 'Login flow', 'start_date' => '2026-10-11', 'end_date' => '2026-10-24'], $data), $title);
    }

    public function test_sprint_dates_must_be_in_order(): void
    {
        $delivery = $this->operatingUser('delivery');
        $sprint = ['title' => 'Sprint 1', 'reason' => 'Plan', 'data' => ['project_id' => $this->project(), 'goal' => 'Login', 'start_date' => '2026-10-24', 'end_date' => '2026-10-11']];

        $this->actingAs($delivery)->post('/admin/operations/sprints', $sprint)->assertSessionHasErrors('workflow');
        $sprint['data']['end_date'] = '2026-10-30';
        $this->actingAs($delivery)->post('/admin/operations/sprints', $sprint)->assertSessionHasNoErrors();
    }

    public function test_one_active_sprint_per_executing_project_and_stages_in_order(): void
    {
        $owner = $this->operatingUser('owner');
        $project = $this->project();
        $this->sprint($project, 'active');
        $second = $this->sprint($project, 'planned', 'Sprint 2');

        $this->actingAs($owner)->post("/admin/operations/record/{$second}/transition", ['target' => 'active', 'version' => 1, 'reason' => 'Start'])->assertSessionHasErrors('workflow');
        $this->actingAs($owner)->post("/admin/operations/record/{$second}/transition", ['target' => 'completed', 'version' => 1, 'reason' => 'Skip'])->assertSessionHasErrors('workflow');

        $planning = $this->sprint($this->project('planning'));
        $this->actingAs($owner)->post("/admin/operations/record/{$planning}/transition", ['target' => 'active', 'version' => 1, 'reason' => 'Start'])->assertSessionHasErrors('workflow');

        $free = $this->sprint($this->project());
        $this->actingAs($owner)->post("/admin/operations/record/{$free}/transition", ['target' => 'active', 'version' => 1, 'reason' => 'Start'])->assertSessionHasNoErrors();
    }

    public function test_completing_a_sprint_returns_unfinished_tasks_to_the_backlog(): void
    {
        $owner = $this->operatingUser('owner');
        $project = $this->project();
        $sprint = $this->sprint($project, 'active');
        $done = $this->operatingRecord('tasks', 'done', ['project_id' => $project, 'sprint_id' => $sprint], 'Done task');
        $open = $this->operatingRecord('tasks', 'in_progress', ['project_id' => $project, 'sprint_id' => $sprint], 'Open task');

        $this->actingAs($owner)->post("/admin/operations/record/{$sprint}/transition", ['target' => 'completed', 'version' => 1, 'reason' => 'Sprint review'])->assertSessionHasNoErrors();

        $this->assertNull($this->recordData($open)['sprint_id']);
        $this->assertSame($sprint, $this->recordData($open)['carried_from_sprint']);
        $this->assertSame('in_progress', DB::table('operating_records')->where('id', $open)->value('status'));
        $this->assertFalse(DB::table('operating_links')->where('record_id', $open)->where('field', 'sprint_id')->exists());
        $this->assertTrue(DB::table('operating_events')->where('record_id', $open)->where('action', 'update')->exists());
        $this->assertSame($sprint, $this->recordData($done)['sprint_id']);
        $this->assertSame(['done' => 1, 'carried' => 1], $this->recordData($sprint)['completed_summary']);
    }

    public function test_tasks_join_only_open_sprints_of_their_own_project(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $project = $this->project();
        $task = fn (int $sprint): array => ['title' => 'Login', 'reason' => 'Plan', 'data' => ['project_id' => $project, 'sprint_id' => $sprint]];

        $this->actingAs($lead)->post('/admin/operations/tasks', $task($this->sprint($this->project())))->assertSessionHasErrors('workflow');
        $this->actingAs($lead)->post('/admin/operations/tasks', $task($this->sprint($project, 'completed')))->assertSessionHasErrors('workflow');
        $this->actingAs($lead)->post('/admin/operations/tasks', $task($this->sprint($project)))->assertSessionHasNoErrors();
    }

    public function test_developers_see_only_sprints_of_their_projects(): void
    {
        $developer = $this->operatingUser('developer');
        $employee = $this->employeeFor($developer);
        $mine = $this->project();
        $this->operatingRecord('project_members', 'active', ['project_id' => $mine, 'assignee_id' => $employee, 'member_role' => 'developer'], 'Member');
        $this->sprint($mine, 'planned', 'Mine');
        $this->sprint($this->project(), 'planned', 'Other');

        $titles = collect($this->actingAs($developer)->getJson('/admin/operations/sprints?format=json')->json('data'))->pluck('title')->all();
        $this->assertSame(['Mine'], $titles);
    }
}
