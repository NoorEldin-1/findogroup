<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingSprints;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SprintBoardTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
        $this->project = $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    private function sprint(string $status = 'active'): int
    {
        return $this->operatingRecord('sprints', $status, ['project_id' => $this->project, 'goal' => 'Login', 'start_date' => '2026-10-11', 'end_date' => '2026-10-20'], 'Sprint 1');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function task(string $status, string $title, array $data = []): int
    {
        return $this->operatingRecord('tasks', $status, array_merge(['project_id' => $this->project], $data), $title);
    }

    public function test_sprint_progress_counts_tasks_hours_time_and_overdue_work(): void
    {
        $sprint = $this->sprint();
        $this->task('done', 'Done', ['sprint_id' => $sprint, 'estimate_hours' => 4]);
        $this->task('in_progress', 'Doing', ['sprint_id' => $sprint, 'estimate_hours' => 6, 'remaining_hours' => 2]);
        $this->task('ready', 'Late', ['sprint_id' => $sprint, 'estimate_hours' => 5, 'original_due' => '2026-10-12']);

        $progress = app(OperatingSprints::class)->progress(DB::table('operating_records')->find($sprint));

        $this->assertSame(33, $progress['percent']);
        $this->assertSame(['done' => 1, 'total' => 3], $progress['tasks']);
        $this->assertSame(['estimate' => 15.0, 'remaining' => 7.0], $progress['hours']);
        $this->assertSame(['total' => 10, 'elapsed' => 5, 'left' => 5], $progress['days']);
        $this->assertSame(50, $progress['time_percent']);
        $this->assertSame(1, $progress['overdue_tasks']);
        $this->assertFalse($progress['late']);
    }

    public function test_board_separates_backlog_sprint_columns_and_overdue_tasks(): void
    {
        $sprint = $this->sprint();
        $this->task('in_progress', 'In sprint', ['sprint_id' => $sprint]);
        $this->task('backlog', 'Low item', ['priority' => 'low']);
        $this->task('backlog', 'Critical item', ['priority' => 'critical', 'original_due' => '2026-10-01']);
        $this->task('done', 'Finished');

        $board = app(OperatingSprints::class)->board($this->operatingUser('owner'), DB::table('operating_records')->find($this->project));

        $this->assertSame(['Critical item', 'Low item'], array_column($board['backlog'], 'title'));
        $this->assertSame(['In sprint'], array_column($board['active']['columns']['in_progress'], 'title'));
        $this->assertSame(['Critical item'], array_column($board['overdue'], 'title'));
        $this->actingAs($this->operatingUser('delivery'))->get("/admin/operations/projects/{$this->project}/sprints")->assertOk()->assertSee('Critical item')->assertSee('In sprint');
    }

    public function test_planning_moves_tasks_into_a_sprint_and_back(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $sprint = $this->sprint('planned');
        $task = $this->task('backlog', 'Login API', ['priority' => 'high']);

        $this->actingAs($lead)->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => $sprint, 'task_ids' => [$task], 'reason' => 'Sprint planning'])->assertSessionHasNoErrors();
        $this->assertSame($sprint, $this->recordData($task)['sprint_id']);

        $this->actingAs($lead)->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => '', 'task_ids' => [$task], 'reason' => 'Descoped'])->assertSessionHasNoErrors();
        $this->assertNull($this->recordData($task)['sprint_id']);

        $this->actingAs($this->operatingUser('developer'))->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => $sprint, 'task_ids' => [$task], 'reason' => 'Mine'])->assertForbidden();

        $jira = $this->task('backlog', 'From Jira');
        DB::table('operating_records')->where('id', $jira)->update(['source' => 'jira']);
        $this->actingAs($lead)->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => $sprint, 'task_ids' => [$jira], 'reason' => 'Plan'])->assertSessionHasErrors('workflow');
    }

    public function test_new_assignees_are_notified_once_and_never_for_self_assignment(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $leadEmployee = $this->employeeFor($lead);
        $developer = $this->operatingUser('developer');
        $qa = $this->operatingUser('qa');
        $task = ['title' => 'Login', 'reason' => 'Plan', 'data' => ['project_id' => $this->project, 'assignee_id' => $this->employeeFor($developer), 'tester_id' => $this->employeeFor($qa), 'reviewer_id' => $leadEmployee]];

        $this->actingAs($lead)->post('/admin/operations/tasks', $task)->assertSessionHasNoErrors();
        $id = DB::table('operating_records')->where('type', 'tasks')->where('title', 'Login')->value('id');
        $this->actingAs($lead)->post("/admin/operations/record/{$id}", $task + ['version' => 1])->assertSessionHasNoErrors();

        $this->assertSame(1, $developer->notifications()->count());
        $this->assertSame(1, $qa->notifications()->count());
        $this->assertSame(0, $lead->notifications()->count());
    }

    public function test_only_delivery_roles_open_the_board(): void
    {
        $this->actingAs($this->operatingUser('marketing'))->get("/admin/operations/projects/{$this->project}/sprints")->assertForbidden();
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme'], 'Acme');
        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/projects/{$client}/sprints")->assertNotFound();
    }

    public function test_create_forms_can_be_prefilled_with_a_readable_parent(): void
    {
        $this->actingAs($this->operatingUser('tech_lead'))->get("/admin/operations/sprints/create?prefill[project_id]={$this->project}")->assertOk()->assertSee('value="'.$this->project.'" selected', false);
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme'], 'Acme');
        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/sprints/create?prefill[project_id]={$client}")->assertNotFound();
    }
}
