<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CampaignsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function campaign(array $data = []): array
    {
        return ['title' => 'Ramadan ERP', 'reason' => 'Plan', 'data' => array_merge(['objective' => 'leads', 'budget_cap' => 1000, 'start_date' => '2026-10-11', 'end_date' => '2026-10-31'], $data)];
    }

    private function campaignId(): int
    {
        return $this->operatingRecord('campaigns', 'running', ['channel' => 'facebook', 'objective' => 'leads', 'budget_cap' => 1000], 'Ramadan ERP');
    }

    public function test_campaign_dates_are_ordered_and_a_channel_is_needed_for_approval(): void
    {
        $buyer = $this->operatingUser('media_buyer');
        $this->actingAs($buyer)->post('/admin/operations/campaigns', $this->campaign(['end_date' => '2026-10-01']))->assertSessionHasErrors('workflow');
        $this->actingAs($buyer)->post('/admin/operations/campaigns', $this->campaign())->assertSessionHasNoErrors();

        $id = DB::table('operating_records')->where('type', 'campaigns')->value('id');
        $owner = $this->operatingUser('owner');
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Go'])->assertSessionHasErrors('workflow');
        DB::table('operating_records')->where('id', $id)->update(['data->channel' => 'google']);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Go'])->assertSessionHasNoErrors();
    }

    public function test_a_lead_can_point_to_its_source_campaign(): void
    {
        $campaign = $this->campaignId();
        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/opportunities', ['title' => 'ERP lead', 'reason' => 'Inbound', 'data' => ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook', 'campaign_id' => $campaign, 'need' => 'ERP', 'solution' => 'web', 'next_action' => 'Call', 'follow_up' => '2026-10-20']])->assertSessionHasNoErrors();

        $lead = DB::table('operating_records')->where('type', 'opportunities')->value('id');
        $this->assertTrue(DB::table('operating_links')->where('record_id', $lead)->where('field', 'campaign_id')->where('target_id', $campaign)->exists());
    }

    public function test_only_marketing_leads_create_and_assign_team_tasks(): void
    {
        $designer = $this->operatingUser('designer');
        $designerEmployee = $this->employeeFor($designer, ['department' => 'marketing']);
        $task = ['title' => 'Carousel design', 'reason' => 'Campaign brief', 'data' => ['campaign_id' => $this->campaignId(), 'assignee_id' => $designerEmployee, 'due_date' => '2026-10-20', 'description' => '5 slides', 'priority' => 'high']];

        $this->actingAs($designer)->post('/admin/operations/team_tasks', $task)->assertForbidden();
        $this->actingAs($designer)->get('/admin/operations/team_tasks/create')->assertForbidden();
        $this->actingAs($this->operatingUser('media_buyer'))->post('/admin/operations/team_tasks', $task)->assertSessionHasErrors('workflow');
        $this->actingAs($this->operatingUser('marketing'))->post('/admin/operations/team_tasks', $task)->assertSessionHasNoErrors();
        $this->assertSame(1, $designer->notifications()->count());
    }

    public function test_assignees_see_and_move_only_their_own_tasks_until_review(): void
    {
        $designer = $this->operatingUser('designer');
        $mine = $this->operatingRecord('team_tasks', 'backlog', ['assignee_id' => $this->employeeFor($designer), 'due_date' => '2026-10-20', 'description' => '5 slides'], 'Mine');
        $this->operatingRecord('team_tasks', 'backlog', ['assignee_id' => $this->employeeFor($this->operatingUser('video_editor')), 'due_date' => '2026-10-20', 'description' => 'Reel'], 'Theirs');

        $this->assertSame(['Mine'], collect($this->actingAs($designer)->getJson('/admin/operations/team_tasks?format=json')->json('data'))->pluck('title')->all());

        $this->actingAs($designer)->post("/admin/operations/record/{$mine}", ['title' => 'Mine', 'version' => 1, 'reason' => 'Progress', 'data' => ['description' => 'Changed', 'notes' => 'Draft ready']])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('5 slides', $this->recordData($mine)['description']);
        $this->assertSame('Draft ready', $this->recordData($mine)['notes']);

        $this->actingAs($designer)->post("/admin/operations/record/{$mine}/transition", ['target' => 'review', 'version' => 2, 'reason' => 'Ready'])->assertSessionHasNoErrors();
        $this->actingAs($designer)->post("/admin/operations/record/{$mine}/transition", ['target' => 'done', 'version' => 3, 'reason' => 'Close'])->assertForbidden();
        $this->actingAs($this->operatingUser('marketing'))->post("/admin/operations/record/{$mine}/transition", ['target' => 'done', 'version' => 3, 'reason' => 'Approved'])->assertSessionHasNoErrors();
    }
}
