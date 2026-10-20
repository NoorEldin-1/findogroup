<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmCalendarTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_the_grid_starts_on_saturday_and_places_items_with_overdue_flags(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser);
        $other = $this->employeeFor($this->operatingUser('sales'));
        $lead = $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Co', 'assignee_id' => $rep], 'Co');
        $this->operatingRecord('activities', 'planned', ['kind' => 'meeting', 'assignee_id' => $rep, 'due_at' => '2026-10-19T10:00', 'opportunity_id' => $lead], 'Demo');
        $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $rep, 'due_at' => '2026-10-22T09:30'], 'Call back');
        $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $other, 'due_at' => '2026-10-22T11:00'], 'Not mine');

        $calendar = app(OperatingCrmReports::class)->calendar($repUser, '2026-10');

        $this->assertSame('2026-09-26', $calendar['weeks'][0][0]['date']);
        $this->assertSame('2026-11-06', last(last($calendar['weeks']))['date']);
        $days = collect($calendar['weeks'])->flatten(1)->keyBy('date');
        $this->assertTrue($days['2026-10-19']['items'][0]['overdue']);
        $this->assertSame($lead, $days['2026-10-19']['items'][0]['lead']);
        $this->assertSame(['Call back'], array_column($days['2026-10-22']['items'], 'title'));
        $this->assertTrue($days['2026-10-20']['today']);
    }

    public function test_the_page_renders_and_new_activity_links_prefill_the_due_time(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin/operations/crm/calendar?month=2026-10')->assertOk()->assertSee('prefill[due_at]=2026-10-22T10:00', false);
        $this->actingAs($rep)->get('/admin/operations/activities/create?prefill[due_at]=2026-10-22T10:00')->assertOk()->assertSee('value="2026-10-22T10:00"', false);
        $this->actingAs($rep)->get('/admin/operations/crm/calendar?month=2026-13')->assertSessionHasErrors('month');
    }
}
