<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMilestones;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MilestonesTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
        $this->project = $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function milestone(string $status = 'planned', array $data = []): int
    {
        return $this->operatingRecord('milestones', $status, array_merge(['project_id' => $this->project, 'deliverable' => 'Phase 1', 'acceptance_criteria' => 'Signed off', 'original_due' => '2026-11-01'], $data), 'Phase 1');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function deliverable(int $milestone, string $status, string $due = '2026-10-20', array $data = []): int
    {
        return $this->operatingRecord('deliverables', $status, array_merge(['milestone_id' => $milestone, 'project_id' => $this->project, 'due_date' => $due], $data), 'Login screens');
    }

    public function test_deliverables_inherit_the_milestone_project_and_respect_its_due_date(): void
    {
        $delivery = $this->operatingUser('delivery');
        $milestone = $this->milestone();
        $payload = ['title' => 'Login screens', 'reason' => 'Plan', 'data' => ['milestone_id' => $milestone, 'due_date' => '2026-10-20']];

        $this->actingAs($delivery)->post('/admin/operations/deliverables', $payload)->assertSessionHasNoErrors();
        $id = DB::table('operating_records')->where('type', 'deliverables')->value('id');
        $this->assertSame($this->project, $this->recordData($id)['project_id']);
        $this->assertTrue(DB::table('operating_links')->where('record_id', $id)->where('field', 'project_id')->where('target_id', $this->project)->exists());

        $payload['data']['due_date'] = '2026-12-30';
        $this->actingAs($delivery)->post('/admin/operations/deliverables', $payload)->assertSessionHasErrors('workflow');

        $payload['data']['due_date'] = '2026-10-20';
        $payload['data']['milestone_id'] = $this->milestone('submitted');
        $this->actingAs($delivery)->post('/admin/operations/deliverables', $payload)->assertSessionHasErrors('workflow');
    }

    public function test_a_deliverable_is_delivered_only_with_a_link_or_evidence(): void
    {
        $owner = $this->operatingUser('owner');
        $milestone = $this->milestone();
        $id = $this->deliverable($milestone, 'in_progress');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'delivered', 'version' => 1, 'reason' => 'Done'])->assertSessionHasErrors('workflow');
        DB::table('operating_records')->where('id', $id)->update(['data->delivery_url' => 'https://figma.test/login']);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'delivered', 'version' => 1, 'reason' => 'Done'])->assertSessionHasNoErrors();
    }

    public function test_milestones_need_an_owner_delivered_outputs_and_submission_before_acceptance(): void
    {
        $owner = $this->operatingUser('owner');
        $employee = $this->employeeFor($this->operatingUser('delivery'));
        $unowned = $this->milestone();
        $this->actingAs($owner)->post("/admin/operations/record/{$unowned}/transition", ['target' => 'in_progress', 'version' => 1, 'reason' => 'Start'])->assertSessionHasErrors('workflow');

        $milestone = $this->milestone('in_progress', ['assignee_id' => $employee, 'client_acceptance' => 'Email', 'accepted_at' => '2026-10-15']);
        $deliverable = $this->deliverable($milestone, 'in_progress');
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'accepted', 'version' => 1, 'reason' => 'Skip'])->assertSessionHasErrors('workflow');
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'submitted', 'version' => 1, 'reason' => 'Send'])->assertSessionHasErrors('workflow');

        DB::table('operating_records')->where('id', $deliverable)->update(['status' => 'delivered']);
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'submitted', 'version' => 1, 'reason' => 'Send'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'accepted', 'version' => 2, 'reason' => 'Client signed'])->assertSessionHasNoErrors();
    }

    public function test_progress_counts_delivered_outputs_and_flags_delays(): void
    {
        $milestones = app(OperatingMilestones::class);
        $late = $this->milestone('in_progress', ['original_due' => '2026-10-10']);
        $this->deliverable($late, 'delivered', '2026-10-05');
        $this->deliverable($late, 'in_progress', '2026-10-08');

        $progress = $milestones->progress(DB::table('operating_records')->find($late));
        $this->assertSame(50, $progress['percent']);
        $this->assertSame(1, $progress['delayed']);
        $this->assertTrue($progress['overdue']);

        $this->assertFalse($milestones->progress(DB::table('operating_records')->find($this->milestone('submitted', ['original_due' => '2026-10-01'])))['overdue']);
        $this->assertTrue($milestones->progress(DB::table('operating_records')->find($this->milestone('planned', ['original_due' => '2026-10-20'])))['due_soon']);
        $this->assertSame(100, $milestones->progress(DB::table('operating_records')->find($this->milestone('accepted')))['percent']);
    }

    public function test_the_milestone_page_lists_its_deliverables(): void
    {
        $milestone = $this->milestone();
        $this->deliverable($milestone, 'planned');

        $this->actingAs($this->operatingUser('delivery'))->get("/admin/operations/record/{$milestone}")->assertOk()->assertSee(__('مخرجات المحطة'))->assertSee('Login screens');
    }
}
