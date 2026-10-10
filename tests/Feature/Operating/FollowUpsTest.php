<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingFollowUps;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class FollowUpsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    private function client(): int
    {
        return $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function activity(int $assigneeId, string $dueAt, array $data = [], string $status = 'planned'): int
    {
        return $this->operatingRecord('activities', $status, array_merge(['kind' => 'call', 'assignee_id' => $assigneeId, 'due_at' => $dueAt, 'client_id' => $this->client()], $data), 'Call '.$dueAt);
    }

    public function test_a_follow_up_needs_a_client_or_lead_and_inherits_the_lead_client(): void
    {
        $sales = $this->operatingUser('sales');
        $employee = $this->employeeFor($sales);
        $clientId = $this->client();
        $leadId = $this->operatingRecord('opportunities', 'new', ['client_id' => $clientId, 'lead_company' => 'Acme'], 'Lead', $sales->id);
        $payload = ['title' => 'Call Mona', 'reason' => 'Plan call', 'data' => ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-11T11:00']];

        $this->actingAs($sales)->post('/admin/operations/activities', $payload)->assertSessionHasErrors('workflow');
        $payload['data']['opportunity_id'] = $leadId;
        $this->actingAs($sales)->post('/admin/operations/activities', $payload)->assertSessionHasNoErrors();

        $activity = DB::table('operating_records')->where('type', 'activities')->sole();
        $this->assertSame($clientId, json_decode($activity->data, true)['client_id']);
        $this->assertSame('planned', $activity->status);
        $this->assertTrue(DB::table('operating_links')->where('record_id', $activity->id)->where('field', 'client_id')->where('target_id', $clientId)->exists());
    }

    public function test_a_rep_cannot_assign_a_follow_up_to_a_colleague(): void
    {
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);
        $colleague = $this->employeeFor($this->operatingUser('sales'));

        $this->actingAs($sales)->post('/admin/operations/activities', ['title' => 'Call', 'reason' => 'Plan', 'data' => ['kind' => 'call', 'assignee_id' => $colleague, 'due_at' => '2026-10-11T11:00', 'client_id' => $this->client()]])->assertSessionHasErrors('workflow');
    }

    public function test_completing_requires_an_outcome_and_stamps_the_time(): void
    {
        $sales = $this->operatingUser('sales');
        $id = $this->activity($this->employeeFor($sales), '2026-10-10T09:00');

        $this->actingAs($sales)->post("/admin/operations/record/{$id}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Called'])->assertSessionHasErrors('workflow');
        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(array_merge($this->recordData($id), ['outcome' => 'Interested, wants a demo']))]);
        $this->actingAs($sales)->post("/admin/operations/record/{$id}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Called'])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-10T10:00', $this->recordData($id)['completed_at']);
    }

    public function test_the_board_splits_overdue_today_upcoming_and_overdue_lead_follow_ups(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $employee = $this->employeeFor($this->operatingUser('sales'));
        $this->activity($employee, '2026-10-09T15:00');
        $this->activity($employee, '2026-10-10T16:00');
        $this->activity($employee, '2026-10-13T10:00');
        $this->activity($employee, '2026-11-20T10:00');
        $this->activity($employee, '2026-10-08T10:00', ['outcome' => 'Done'], 'done');
        $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Late', 'follow_up' => '2026-10-01'], 'Late lead');

        $board = app(OperatingFollowUps::class)->board($manager);

        $this->assertSame(['overdue' => 1, 'today' => 1, 'week' => 1, 'later' => 1, 'leads' => 1, 'done' => 1], $board['counts']);
        $this->actingAs($manager)->get('/admin/operations/activities/follow-ups')->assertOk()->assertSee('Late lead')->assertSee('Call 2026-10-09T15:00');
    }

    public function test_reminders_notify_once_and_rescheduling_rearms_them(): void
    {
        $salesUser = $this->operatingUser('sales');
        $managerUser = $this->operatingUser('delivery');
        $managerEmployee = $this->employeeFor($managerUser);
        $employee = $this->employeeFor($salesUser, ['manager_id' => $managerEmployee]);
        $salesManager = $this->operatingUser('sales_manager');
        $soon = $this->activity($employee, '2026-10-10T10:30');
        $late = $this->activity($employee, '2026-10-10T08:00');
        $this->activity($employee, '2026-10-10T13:00');

        $this->artisan('operating:follow-up-reminders')->assertSuccessful();
        $this->artisan('operating:follow-up-reminders')->assertSuccessful();

        $this->assertSame(2, $salesUser->notifications()->count());
        $this->assertSame(1, $managerUser->notifications()->count());
        $this->assertSame(1, $salesManager->notifications()->count());
        $this->assertNotNull($this->recordData($soon)['reminded_at']);
        $this->assertNotNull($this->recordData($late)['overdue_alerted_at']);

        $this->actingAs($salesUser)->post("/admin/operations/record/{$soon}", ['title' => 'Call', 'version' => 1, 'reason' => 'Client asked to move', 'data' => array_merge($this->recordData($soon), ['due_at' => '2026-10-10T10:45'])])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('reminded_at', $this->recordData($soon));
    }

    public function test_a_rep_only_sees_their_own_follow_ups(): void
    {
        $sales = $this->operatingUser('sales');
        $this->activity($this->employeeFor($sales), '2026-10-11T10:00');
        $this->activity($this->employeeFor($this->operatingUser('sales')), '2026-10-11T10:00');

        $this->assertCount(1, $this->actingAs($sales)->getJson('/admin/operations/activities?format=json')->json('data'));
    }
}
