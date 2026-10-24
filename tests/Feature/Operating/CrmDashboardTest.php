<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use App\Services\OperatingLeadRelations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmDashboardTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     */
    private function lead(string $status, array $data, string $createdAt, ?int $owner = null): int
    {
        $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co'], 'Lead '.$status.' '.$createdAt, $owner);
        DB::table('operating_records')->where('id', $id)->update(['created_at' => $createdAt]);

        return $id;
    }

    public function test_the_eight_indicators_follow_their_definitions(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $manager = $this->operatingUser('sales_manager');
        $employee = $this->employeeFor($this->operatingUser('sales'));
        $won = $this->lead('won', ['expected_value' => 1000, 'currency' => 'USD', 'closed_at' => '2026-10-10T10:00'], '2026-10-02 10:00:00');
        $this->transitionEvent($won, 'meeting');
        $lostAfterMeeting = $this->lead('lost', [], '2026-10-03 10:00:00');
        $this->transitionEvent($lostAfterMeeting, 'meeting');
        $this->lead('contacted', ['expected_value' => 500, 'currency' => 'EGP'], '2026-10-04 10:00:00');
        $this->lead('new', ['expected_value' => 200, 'currency' => 'USD', 'sla_due_at' => '2026-10-05T10:00'], '2026-10-05 09:00:00');
        $this->lead('won', ['expected_value' => 9000, 'currency' => 'USD', 'closed_at' => '2026-09-20T10:00'], '2026-09-01 10:00:00');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-06T10:00', 'completed_at' => '2026-10-06T10:00', 'opportunity_id' => $won], 'Call');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-09-06T10:00', 'completed_at' => '2026-09-06T10:00', 'opportunity_id' => $won], 'Old call');

        $kpis = app(OperatingCrmReports::class)->dashboard($manager, '2026-10-01', '2026-10-31', 'team')['kpis'];

        $this->assertSame(4, $kpis['total_leads']);
        $this->assertSame(1, $kpis['activities']);
        $this->assertSame(1, $kpis['won']);
        $this->assertEquals(['USD' => 1000.0], $kpis['revenue']);
        $this->assertEquals(['EGP' => 500.0, 'USD' => 200.0], $kpis['potential']);
        $this->assertSame(1, $kpis['sla_alerts']);
        $this->assertSame(66.7, $kpis['contact_to_meeting']);
        $this->assertSame(50.0, $kpis['meeting_to_close']);
    }

    public function test_a_rep_only_counts_their_own_leads_and_managers_can_switch_to_mine(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser);
        $this->lead('new', ['assignee_id' => $rep], '2026-10-02 10:00:00');
        $this->lead('new', [], '2026-10-03 10:00:00');
        $manager = $this->operatingUser('sales_manager');

        $this->assertSame(1, app(OperatingCrmReports::class)->dashboard($repUser, '2026-10-01', '2026-10-31', 'team')['kpis']['total_leads']);
        $this->assertSame(2, app(OperatingCrmReports::class)->dashboard($manager, '2026-10-01', '2026-10-31', 'team')['kpis']['total_leads']);
        $this->assertSame(0, app(OperatingCrmReports::class)->dashboard($manager, '2026-10-01', '2026-10-31', 'mine')['kpis']['total_leads']);
    }

    public function test_the_page_renders_for_sales_and_is_refused_to_other_roles(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin/operations/crm')->assertOk()->assertSee(__('الإيراد المحتمل'))->assertSee(__('تحويل التواصل للاجتماع'))->assertSee('2026-10-01');
        $this->actingAs($rep)->get('/admin/operations/crm?from=2026-10-10&to=2026-10-01')->assertSessionHasErrors('to');
        $this->actingAs($this->operatingUser('designer'))->get('/admin/operations/crm')->assertForbidden();
    }

    public function test_the_shared_and_following_tabs_list_their_leads_with_empty_states(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);
        $lead = DB::table('operating_records')->find($this->lead('new', [], '2026-10-02 10:00:00', $owner->id));
        $relations = app(OperatingLeadRelations::class);

        $this->actingAs($rep)->get('/admin/operations/crm?tab=shared')->assertOk()->assertSee(__('لا يوجد عملاء مشتركون معك.'));
        $relations->share($owner, $lead, $rep->id);
        $relations->toggleFollow($rep, $lead);

        $dashboard = app(OperatingCrmReports::class)->dashboard($rep, '2026-10-01', '2026-10-31', 'mine');
        $this->assertSame([$lead->id], array_column($dashboard['shared'], 'id'));
        $this->assertSame([$lead->id], array_column($dashboard['following'], 'id'));
        $this->assertSame(['leads' => 0, 'shared' => 1, 'following' => 1], $dashboard['tabCounts']);
        $this->actingAs($rep)->get('/admin/operations/crm?tab=following')->assertOk()->assertSee($lead->title);
        $this->actingAs($rep)->get('/admin/operations/crm/leads?relation=shared')->assertOk()->assertSee($lead->title);
        $this->actingAs($rep)->get('/admin/operations/crm?tab=bogus')->assertSessionHasErrors('tab');
    }

    public function test_the_my_clients_button_toggles_the_manager_scope_and_links_reps_to_their_leads(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $manager = $this->operatingUser('sales_manager');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($manager)->get('/admin/operations/crm')->assertOk()->assertSee('scope=mine', false);
        $this->actingAs($manager)->get('/admin/operations/crm?scope=mine')->assertOk()->assertSee('scope=team', false);
        $this->actingAs($rep)->get('/admin/operations/crm')->assertOk()->assertSee('class="hero-button" href="/admin/operations/crm/leads"', false)->assertDontSee('scope=mine', false);
    }

    public function test_todays_tasks_show_kind_and_overdue_status(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $employee = $this->employeeFor($rep);
        $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-20T09:00'], 'Morning call');
        $this->operatingRecord('activities', 'planned', ['kind' => 'meeting', 'assignee_id' => $employee, 'due_at' => '2026-10-20T15:00'], 'Afternoon meeting');

        $this->actingAs($rep)->get('/admin/operations/crm')->assertOk()->assertSeeInOrder(['Morning call', __('متأخرة'), '09:00', 'Afternoon meeting', __('اليوم'), '15:00']);
    }
}
