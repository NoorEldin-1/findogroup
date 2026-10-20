<?php

namespace Tests\Feature\Operating;

use App\Models\User;
use App\Services\Spreadsheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadsListTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $rep;

    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-20 12:00:00');
        $this->rep = $this->employeeFor($this->operatingUser('sales'));
        $this->other = $this->employeeFor($this->operatingUser('sales'));
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Hot Foods', 'lead_phone' => '01001112223', 'phone_key' => '001112223', 'lead_quality' => 'hot', 'assignee_id' => $this->rep, 'sla_due_at' => '2026-10-20T10:00'], 'Hot Foods');
        $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Cold Tech', 'lead_phone' => '01009998887', 'phone_key' => '009998887', 'lead_quality' => 'cold', 'assignee_id' => $this->other, 'first_contact_at' => '2026-10-19T09:00', 'sla_due_at' => '2026-10-19T10:00'], 'Cold Tech');
        $this->operatingRecord('opportunities', 'archived', ['lead_company' => 'Gone'], 'Gone');
    }

    public function test_managers_filter_by_stage_quality_phone_and_breached_sla(): void
    {
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->get('/admin/operations/crm/leads')->assertOk()->assertSee('Hot Foods')->assertSee('Cold Tech')->assertDontSee('Gone');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?stage=meeting')->assertSee('Cold Tech')->assertDontSee('Hot Foods');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?quality=hot')->assertSee('Hot Foods')->assertDontSee('Cold Tech');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?q=+20 100 999 8887')->assertSee('Cold Tech')->assertDontSee('Hot Foods');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?sla=breached')->assertSee('Hot Foods')->assertDontSee('Cold Tech');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?assignee='.$this->other)->assertSee('Cold Tech')->assertDontSee('Hot Foods');
    }

    public function test_a_rep_only_sees_their_own_leads_and_chosen_columns(): void
    {
        $rep = DB::table('users')->where('role', 'sales')->orderBy('id')->first();
        $user = User::find($rep->id);

        $this->actingAs($user)->get('/admin/operations/crm/leads?columns[]=title&columns[]=lead_quality')->assertOk()->assertSee('Hot Foods')->assertDontSee('Cold Tech')->assertSee(__('جودة العميل'))->assertDontSee('<th>'.__('الهاتف').'</th>', false);
    }

    public function test_export_writes_an_audited_workbook_with_masked_phones(): void
    {
        $manager = $this->operatingUser('sales_manager');

        $response = $this->actingAs($manager)->post('/admin/operations/crm/leads/export', ['columns' => ['title', 'lead_phone', 'sla'], 'mask_phones' => '1', 'stage' => 'new', 'reason' => 'Weekly review'])->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, $response->getContent());
        $rows = app(Spreadsheet::class)->read($path, 'xlsx');
        $this->assertSame([__('اسم العميل'), __('الهاتف'), 'SLA'], $rows[0]);
        $this->assertSame(['Hot Foods', '•••••••2223', __('متجاوز')], $rows[1]);
        $this->assertCount(2, $rows);
        $this->assertTrue(DB::table('operating_events')->where('action', 'export_leads')->where('reason', 'Weekly review')->exists());
    }

    public function test_roles_without_lead_access_are_refused(): void
    {
        $this->actingAs($this->operatingUser('designer'))->get('/admin/operations/crm/leads')->assertForbidden();
        $this->actingAs($this->operatingUser('designer'))->post('/admin/operations/crm/leads/export', ['columns' => ['title']])->assertForbidden();
    }
}
