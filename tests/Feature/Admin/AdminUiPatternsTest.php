<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AdminUiPatternsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_entity_listing_shows_labelled_filter_panel_and_total_while_still_filtering(): void
    {
        $owner = $this->operatingUser('owner');
        $this->operatingRecord('projects', 'discovery', [], 'Discovery Project');
        $this->operatingRecord('projects', 'execution', [], 'Running Project');

        $this->actingAs($owner)->get('/admin/operations/projects')->assertOk()
            ->assertSee('data-filter-panel', false)
            ->assertSee('<label class="field"><span>'.__('الحالة').'</span>', false)
            ->assertSee('<span class="head-stat"><b>2</b>', false);

        $this->actingAs($owner)->get('/admin/operations/projects?status=execution')->assertOk()
            ->assertSee('Running Project')->assertDontSee('Discovery Project')
            ->assertSee('<span class="head-stat"><b>1</b>', false);
    }

    public function test_leads_page_offers_excel_export_from_the_page_header(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Header Co'], 'Header Co');

        $html = $this->actingAs($manager)->get('/admin/operations/crm/leads')->assertOk()->getContent();
        $heading = substr($html, strpos($html, 'class="page-heading"'), strpos($html, 'data-filter-panel') - strpos($html, 'class="page-heading"'));

        $this->assertStringContainsString('action="/admin/operations/crm/leads/export"', $heading);
        $this->assertStringContainsString('<span class="head-stat"><b>1</b>', $heading);
    }

    public function test_contents_type_filter_can_select_posts(): void
    {
        $this->actingAs($this->operatingUser('owner'))->get('/admin/contents?kind=post')->assertOk()
            ->assertSee('<option value="post" selected>', false);
    }

    public function test_audit_log_shows_each_event_with_the_actor_avatar(): void
    {
        $owner = $this->operatingUser('owner');
        DB::table('users')->where('id', $owner->id)->update(['name' => 'Zeina Owner']);
        DB::table('operating_events')->insert(['record_id' => null, 'user_id' => $owner->id, 'action' => 'export_leads', 'reason' => 'Weekly export', 'before' => null, 'after' => null, 'created_at' => now()]);

        $this->actingAs($owner)->get('/admin/operations/audit-log')->assertOk()
            ->assertSee('<ol class="timeline people">', false)
            ->assertSee('<span class="avatar" aria-hidden="true">Z</span>', false);
    }
}
