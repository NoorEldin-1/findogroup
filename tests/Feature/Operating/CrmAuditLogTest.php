<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmAuditLogTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_managers_see_sales_events_with_field_changes_but_not_hr_events(): void
    {
        $owner = $this->operatingUser('owner');
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook'], 'Nile Foods');
        $this->actingAs($owner)->post("/admin/operations/record/{$lead}", ['title' => 'Nile Foods', 'version' => 1, 'reason' => 'Fix quality', 'data' => ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook', 'lead_quality' => 'hot']])->assertSessionHasNoErrors();
        $employee = $this->operatingRecord('employees', 'active', ['job_title' => 'HR'], 'Secret Salary Person');
        DB::table('operating_events')->insert(['record_id' => $employee, 'user_id' => $owner->id, 'action' => 'update', 'reason' => 'Salary change', 'before' => null, 'after' => null, 'created_at' => now()]);
        DB::table('operating_events')->insert(['record_id' => null, 'user_id' => $owner->id, 'action' => 'export_leads', 'reason' => 'Weekly export', 'before' => null, 'after' => null, 'created_at' => now()]);

        $manager = $this->operatingUser('sales_manager');
        $this->actingAs($manager)->get('/admin/operations/audit-log')->assertOk()->assertSee('Fix quality')->assertSee(__('جودة العميل'))->assertSee('hot')->assertSee('Weekly export')->assertDontSee('Salary change');
        $this->actingAs($owner)->get('/admin/operations/audit-log')->assertOk()->assertSee('Salary change');
        $this->actingAs($owner)->get('/admin/operations/audit-log?type=employees')->assertSee('Salary change')->assertDontSee('Fix quality');
        $this->actingAs($owner)->get('/admin/operations/audit-log?q=Nile')->assertSee('Fix quality')->assertDontSee('Salary change');
    }

    public function test_reps_and_other_roles_are_refused(): void
    {
        $this->actingAs($this->operatingUser('sales'))->get('/admin/operations/audit-log')->assertForbidden();
        $this->actingAs($this->operatingUser('hr'))->get('/admin/operations/audit-log')->assertForbidden();
    }
}
