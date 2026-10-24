<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAccess;
use App\Services\OperatingLeadRelations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadRelationsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_sharing_a_lead_gives_the_colleague_read_but_not_write_access(): void
    {
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $colleague = $this->operatingUser('sales');
        $this->employeeFor($colleague);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Co'], 'Shared lead', $owner->id);
        $access = app(OperatingAccess::class);

        $this->assertFalse($access->query($colleague, 'opportunities')->where('id', $lead)->exists());

        app(OperatingLeadRelations::class)->share($owner, DB::table('operating_records')->find($lead), $colleague->id);

        $this->assertTrue($access->query($colleague, 'opportunities')->where('id', $lead)->exists());
        $this->assertFalse($access->query($colleague, 'opportunities', 'write')->where('id', $lead)->exists());
        $this->assertDatabaseHas('operating_events', ['record_id' => $lead, 'action' => 'share']);
        $this->assertSame(1, $colleague->notifications()->count());
    }

    public function test_unsharing_removes_access_and_following_toggles(): void
    {
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $colleague = $this->operatingUser('sales');
        $lead = DB::table('operating_records')->find($this->operatingRecord('opportunities', 'new', [], 'Lead', $owner->id));
        $relations = app(OperatingLeadRelations::class);

        $relations->share($owner, $lead, $colleague->id);
        $relations->unshare($owner, $lead, $colleague->id);

        $this->assertFalse(app(OperatingAccess::class)->query($colleague, 'opportunities')->where('id', $lead->id)->exists());
        $this->assertDatabaseHas('operating_events', ['record_id' => $lead->id, 'action' => 'unshare']);
        $this->assertTrue($relations->toggleFollow($owner, $lead));
        $this->assertSame([$lead->id], $relations->leadIds($owner, OperatingLeadRelations::FOLLOWING)->all());
        $this->assertFalse($relations->toggleFollow($owner, $lead));
    }

    public function test_the_lead_page_shares_and_follows_through_http(): void
    {
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $colleague = $this->operatingUser('sales');
        $this->employeeFor($colleague);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Co'], 'Lead', $owner->id);
        $url = '/admin/operations/crm/leads/'.$lead;

        $this->actingAs($owner)->get($url)->assertOk()->assertSee(__('المشاركة والمتابعة'))->assertSee($url.'/share', false);
        $this->actingAs($colleague)->post($url.'/share', ['user_id' => $owner->id])->assertForbidden();
        $this->actingAs($owner)->post($url.'/share', ['user_id' => $colleague->id])->assertRedirect();
        $this->actingAs($colleague)->get($url)->assertOk()->assertDontSee($url.'/share', false)->assertDontSee('/admin/operations/record/'.$lead.'"', false);
        $this->actingAs($colleague)->post($url.'/follow')->assertRedirect();
        $this->assertDatabaseHas('crm_lead_relations', ['lead_id' => $lead, 'user_id' => $colleague->id, 'relation' => 'following']);
        $this->actingAs($owner)->post($url.'/unshare', ['user_id' => $colleague->id])->assertRedirect();
        $this->actingAs($colleague)->get($url)->assertForbidden();
    }

    public function test_a_shared_colleague_cannot_move_the_stage_or_see_its_quotes(): void
    {
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $colleague = $this->operatingUser('sales');
        $this->employeeFor($colleague);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Co'], 'Lead', $owner->id);
        $quote = $this->operatingRecord('quotes', 'draft', ['opportunity_id' => $lead], 'Quote', $owner->id);
        app(OperatingLeadRelations::class)->share($owner, DB::table('operating_records')->find($lead), $colleague->id);

        $this->actingAs($colleague)->post('/admin/operations/record/'.$lead.'/transition', ['target' => 'contacted', 'version' => 1, 'reason' => 'x'])->assertForbidden();
        $this->assertSame('new', DB::table('operating_records')->find($lead)->status);
        $this->assertFalse(app(OperatingAccess::class)->query($colleague, 'quotes')->where('id', $quote)->exists());
    }

    public function test_a_lead_cannot_be_shared_with_yourself_or_a_user_without_crm_access(): void
    {
        $owner = $this->operatingUser('sales');
        $lead = DB::table('operating_records')->find($this->operatingRecord('opportunities', 'new', [], 'Lead', $owner->id));
        $relations = app(OperatingLeadRelations::class);

        $this->assertArrayNotHasKey($owner->id, $relations->shareableUsers($owner, $lead));
        $this->assertArrayNotHasKey($this->operatingUser('designer')->id, $relations->shareableUsers($owner, $lead));
        $this->expectException(HttpException::class);
        $relations->share($owner, $lead, $owner->id);
    }
}
