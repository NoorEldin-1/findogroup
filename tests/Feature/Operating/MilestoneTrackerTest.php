<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMilestones;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MilestoneTrackerTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 08:00'));
    }

    private function project(?int $managerId = null): int
    {
        return $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona', 'manager_id' => $managerId], 'Portal');
    }

    private function milestone(int $project, string $status, string $due, ?int $owner = null, string $title = 'Phase'): int
    {
        return $this->operatingRecord('milestones', $status, ['project_id' => $project, 'assignee_id' => $owner, 'deliverable' => 'X', 'acceptance_criteria' => 'Y', 'original_due' => $due], $title);
    }

    public function test_project_manager_and_owner_get_one_upcoming_and_one_overdue_alert_per_due_date(): void
    {
        $manager = $this->operatingUser('delivery');
        $owner = $this->operatingUser('developer');
        $ownerEmployee = $this->employeeFor($owner);
        $project = $this->project($this->employeeFor($manager));
        $this->milestone($project, 'in_progress', '2026-10-17', $ownerEmployee);
        $late = $this->milestone($project, 'planned', '2026-10-10', $ownerEmployee);
        $this->milestone($project, 'planned', '2026-11-30', $ownerEmployee);
        $this->milestone($project, 'submitted', '2026-10-01', $ownerEmployee);
        $service = app(OperatingMilestones::class);

        $this->assertSame(['upcoming' => 1, 'overdue' => 1, 'deliverables' => 0], $service->sendReminders(CarbonImmutable::now()));
        $this->assertSame(2, $manager->notifications()->count());
        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame(['upcoming' => 0, 'overdue' => 0, 'deliverables' => 0], $service->sendReminders(CarbonImmutable::now()));

        DB::table('operating_records')->where('id', $late)->update(['data->approved_due' => '2026-10-12']);
        $this->assertSame(1, $service->sendReminders(CarbonImmutable::now())['overdue']);
    }

    public function test_without_a_project_manager_every_delivery_manager_is_alerted(): void
    {
        $first = $this->operatingUser('delivery');
        $second = $this->operatingUser('delivery');
        $this->milestone($this->project(), 'planned', '2026-10-01');

        app(OperatingMilestones::class)->sendReminders(CarbonImmutable::now());

        $this->assertSame(1, $first->notifications()->count());
        $this->assertSame(1, $second->notifications()->count());
    }

    public function test_overdue_deliverables_alert_their_owner_once(): void
    {
        $owner = $this->operatingUser('developer');
        $project = $this->project();
        $milestone = $this->milestone($project, 'in_progress', '2026-11-01');
        $this->operatingRecord('deliverables', 'in_progress', ['milestone_id' => $milestone, 'project_id' => $project, 'assignee_id' => $this->employeeFor($owner), 'due_date' => '2026-10-14'], 'API docs');
        $this->operatingRecord('deliverables', 'delivered', ['milestone_id' => $milestone, 'project_id' => $project, 'due_date' => '2026-10-01'], 'Done docs');

        $this->assertSame(1, app(OperatingMilestones::class)->sendReminders(CarbonImmutable::now())['deliverables']);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(0, app(OperatingMilestones::class)->sendReminders(CarbonImmutable::now())['deliverables']);
    }

    public function test_tracker_buckets_milestones_and_lists_delayed_deliverables(): void
    {
        $project = $this->project();
        $this->milestone($project, 'planned', '2026-10-10', null, 'Late phase');
        $this->milestone($project, 'in_progress', '2026-10-18', null, 'Soon phase');
        $this->milestone($project, 'planned', '2026-12-01', null, 'Later phase');
        $submitted = $this->milestone($project, 'submitted', '2026-10-01', null, 'Sent phase');
        $this->operatingRecord('deliverables', 'planned', ['milestone_id' => $submitted, 'project_id' => $project, 'due_date' => '2026-10-02'], 'Late doc');

        $tracker = app(OperatingMilestones::class)->tracker($this->operatingUser('owner'));

        $this->assertSame(['Late phase'], array_column($tracker['overdue'], 'title'));
        $this->assertSame(['Soon phase'], array_column($tracker['due_soon'], 'title'));
        $this->assertSame(['Later phase'], array_column($tracker['upcoming'], 'title'));
        $this->assertSame(['Sent phase'], array_column($tracker['submitted'], 'title'));
        $this->assertSame(['Late doc'], array_column($tracker['delayed_deliverables'], 'title'));
        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/milestones/tracker')->assertOk()->assertSee('Late phase')->assertSee('Late doc');
        $this->actingAs($this->operatingUser('delivery'))->get("/admin/operations/milestones/tracker?project={$project}")->assertOk()->assertSee('Soon phase');
        $this->actingAs($this->operatingUser('marketing'))->get('/admin/operations/milestones/tracker')->assertForbidden();
    }

    public function test_the_command_prints_what_it_sent(): void
    {
        $this->operatingUser('delivery');
        $this->milestone($this->project(), 'planned', '2026-10-16');

        $this->artisan('operating:milestone-reminders')->expectsOutputToContain('1 upcoming, 0 overdue, 0 deliverables')->assertSuccessful();
    }
}
