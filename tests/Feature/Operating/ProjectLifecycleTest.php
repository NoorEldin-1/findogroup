<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingProjects;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class ProjectLifecycleTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    private function project(string $due = '2026-12-01', string $status = 'execution', string $title = 'Portal'): int
    {
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');

        return $this->operatingRecord('projects', $status, ['client_id' => $client, 'outcome' => 'Portal', 'original_due' => $due, 'client_contact' => 'Mona', 'priority' => 'high', 'project_type' => 'web'], $title);
    }

    private function progress(int $projectId): array
    {
        return app(OperatingProjects::class)->progress(DB::table('operating_records')->find($projectId));
    }

    public function test_progress_uses_tasks_then_milestones_and_counts_jira_done(): void
    {
        $withTasks = $this->project();
        $this->operatingRecord('tasks', 'done', ['project_id' => $withTasks], 'T1');
        $this->operatingRecord('tasks', 'in_progress', ['project_id' => $withTasks], 'T2');
        $this->operatingRecord('tasks', 'backlog', ['project_id' => $withTasks], 'T3');
        $jira = $this->operatingRecord('tasks', 'backlog', ['project_id' => $withTasks, 'source_status' => 'Done'], 'J1');
        DB::table('operating_records')->where('id', $jira)->update(['source' => 'jira']);
        $this->operatingRecord('tasks', 'archived', ['project_id' => $withTasks], 'Old');

        $this->assertSame(50, $this->progress($withTasks)['percent']);
        $this->assertSame(['done' => 2, 'total' => 4], $this->progress($withTasks)['tasks']);

        $withMilestones = $this->project();
        $this->operatingRecord('milestones', 'accepted', ['project_id' => $withMilestones], 'M1');
        $this->operatingRecord('milestones', 'planned', ['project_id' => $withMilestones], 'M2');
        $this->assertSame(50, $this->progress($withMilestones)['percent']);
        $this->assertSame('milestones', $this->progress($withMilestones)['basis']);

        $this->assertNull($this->progress($this->project())['percent']);
        $this->assertSame(100, $this->progress($this->project('2026-01-01', 'closed'))['percent']);
    }

    public function test_overdue_and_due_soon_flags(): void
    {
        $this->assertTrue($this->progress($this->project('2026-10-01'))['overdue']);
        $this->assertTrue($this->progress($this->project('2026-10-20'))['due_soon']);
        $this->assertFalse($this->progress($this->project('2026-10-01', 'closed'))['overdue']);
    }

    public function test_members_are_unique_per_role_and_allocation_is_capped(): void
    {
        $delivery = $this->operatingUser('delivery');
        $projectId = $this->project();
        $employee = $this->employeeFor($this->operatingUser('developer'));
        $member = ['title' => 'Dev on portal', 'reason' => 'Staffing', 'data' => ['project_id' => $projectId, 'assignee_id' => $employee, 'member_role' => 'developer', 'allocation_percent' => 50, 'start_date' => '2026-10-10']];

        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasNoErrors();
        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasErrors('workflow');
        $member['data']['member_role'] = 'qa';
        $member['data']['allocation_percent'] = 150;
        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasErrors('workflow');
        $member['data']['allocation_percent'] = 20;
        $member['data']['end_date'] = '2026-10-01';
        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasErrors('workflow');
        $member['data']['end_date'] = '2026-12-01';
        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasNoErrors();
    }

    public function test_a_developer_sees_projects_they_are_members_of(): void
    {
        $developer = $this->operatingUser('developer');
        $employee = $this->employeeFor($developer);
        $mine = $this->project();
        $this->project();
        $this->operatingRecord('project_members', 'active', ['project_id' => $mine, 'assignee_id' => $employee, 'member_role' => 'developer'], 'Member');

        $this->assertSame([$mine], collect($this->actingAs($developer)->getJson('/admin/operations/projects?format=json')->json('data'))->pluck('id')->all());
    }

    public function test_the_project_page_shows_progress_and_team(): void
    {
        $id = $this->project();
        $this->operatingRecord('tasks', 'done', ['project_id' => $id], 'T1');
        $this->operatingRecord('tasks', 'qa', ['project_id' => $id], 'T2');
        $this->operatingRecord('project_members', 'active', ['project_id' => $id, 'assignee_id' => $this->employeeFor($this->operatingUser('developer')), 'member_role' => 'developer'], 'Nour on portal');

        $this->actingAs($this->operatingUser('delivery'))->get("/admin/operations/record/{$id}")->assertOk()->assertSee('50%')->assertSee('Nour on portal');
    }

    public function test_progress_and_hidden_field_filters_stay_private_to_delivery(): void
    {
        $id = $this->project('2026-12-01', 'execution', 'Secret portal');
        $this->operatingRecord('tasks', 'done', ['project_id' => $id], 'T1');
        $sales = $this->operatingUser('sales_manager');

        $this->actingAs($sales)->get('/admin/operations/projects/portfolio')->assertOk()->assertSee('Secret portal')->assertDontSee('100%');
        $this->actingAs($sales)->get('/admin/operations/projects/portfolio?priority=low')->assertOk()->assertSee('Secret portal');
        $this->actingAs($sales)->get("/admin/operations/record/{$id}")->assertOk()->assertDontSee('100%');
    }

    public function test_developers_only_see_team_rows_of_their_projects(): void
    {
        $developer = $this->operatingUser('developer');
        $employee = $this->employeeFor($developer);
        $mine = $this->project();
        $other = $this->project();
        $this->operatingRecord('project_members', 'active', ['project_id' => $mine, 'assignee_id' => $employee, 'member_role' => 'developer'], 'Me on mine');
        $this->operatingRecord('project_members', 'active', ['project_id' => $other, 'assignee_id' => $this->employeeFor($this->operatingUser('developer')), 'member_role' => 'developer'], 'Someone on other');

        $this->assertSame(['Me on mine'], collect($this->actingAs($developer)->getJson('/admin/operations/project_members?format=json')->json('data'))->pluck('title')->all());
    }

    public function test_the_portfolio_lists_projects_with_progress(): void
    {
        $late = $this->project('2026-10-01', 'execution', 'Late portal');
        $this->operatingRecord('tasks', 'done', ['project_id' => $late], 'T1');
        $this->project('2026-12-01', 'planning', 'Planning app');

        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/projects/portfolio')->assertOk()->assertSee('Late portal')->assertSee('100%')->assertSee('Planning app');
        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/projects/portfolio?status=planning')->assertOk()->assertDontSee('Late portal')->assertSee('Planning app');
        $this->actingAs($this->operatingUser('marketing'))->get('/admin/operations/projects/portfolio')->assertForbidden();
    }
}
