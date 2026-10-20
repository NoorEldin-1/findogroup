<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmTrashTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_archiving_lands_in_the_trash_and_restore_returns_the_previous_stage(): void
    {
        $owner = $this->operatingUser('owner');
        $lead = $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Nile Foods'], 'Nile Foods');

        $this->actingAs($owner)->post("/admin/operations/record/{$lead}/transition", ['target' => 'archived', 'version' => 1, 'reason' => 'Duplicate entry'])->assertSessionHasNoErrors();
        $this->assertSame('meeting', $this->recordData($lead)['archived_from']);

        $manager = $this->operatingUser('sales_manager');
        $this->actingAs($manager)->get('/admin/operations/crm/trash')->assertOk()->assertSee('Nile Foods')->assertSee('Duplicate entry');
        $this->actingAs($manager)->post("/admin/operations/record/{$lead}/restore", ['version' => 2, 'reason' => 'Not a duplicate'])->assertSessionHasNoErrors();

        $record = DB::table('operating_records')->find($lead);
        $this->assertSame(['meeting', 3], [$record->status, $record->version]);
        $this->assertArrayNotHasKey('archived_from', json_decode($record->data, true));
        $this->assertTrue(DB::table('operating_events')->where('record_id', $lead)->where('action', 'restore')->where('reason', 'Not a duplicate')->exists());
    }

    public function test_only_managers_restore_and_only_archived_restorable_records(): void
    {
        $lead = $this->operatingRecord('opportunities', 'archived', ['lead_company' => 'Nile Foods', 'archived_from' => 'new'], 'Nile Foods');
        $open = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Open'], 'Open');
        $project = $this->operatingRecord('projects', 'archived', [], 'Old project');
        $rep = $this->operatingUser('sales');

        $this->actingAs($rep)->get('/admin/operations/crm/trash')->assertForbidden();
        $this->actingAs($rep)->post("/admin/operations/record/{$lead}/restore", ['version' => 1, 'reason' => 'x'])->assertForbidden();
        $owner = $this->operatingUser('owner');
        $this->actingAs($owner)->post("/admin/operations/record/{$open}/restore", ['version' => 1, 'reason' => 'x'])->assertNotFound();
        $this->actingAs($owner)->post("/admin/operations/record/{$project}/restore", ['version' => 1, 'reason' => 'x'])->assertNotFound();
        $this->actingAs($owner)->post("/admin/operations/record/{$lead}/restore", ['version' => 9, 'reason' => 'x'])->assertSessionHasErrors('workflow');
    }
}
