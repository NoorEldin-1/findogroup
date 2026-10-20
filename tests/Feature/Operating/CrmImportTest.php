<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingLeadImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmImportTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_headers_map_automatically_in_english_and_arabic(): void
    {
        $import = app(OperatingLeadImport::class);

        $this->assertSame(['title', 'lead_phone', 'country', 'business_profile', 'lead_quality', 'campaign', 'ad_creative', 'status', 'notes', 'lead_date', 'assignee', 'media_buyer_notes', null], $import->guessMapping(['Client Name', 'Phone', 'Country', 'Business Type', 'Lead Quality', 'Campaign Name', 'Ad Creative', 'Stage', 'Notes', 'Lead Time', 'Sales Agent', 'Media Buyer Notes', 'Random']));
        $this->assertSame(['title', 'lead_phone', 'campaign', 'status', 'media_buyer_notes'], $import->guessMapping(['اسم العميل', 'رقم الجوال', 'الحملة', 'الحالة', 'ملاحظات ميديا باير']));
    }

    public function test_a_manager_imports_with_duplicates_skipped_stages_applied_and_an_audit_event(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $manager = $this->operatingUser('sales_manager');
        $sara = $this->employeeFor($this->operatingUser('sales'));
        DB::table('operating_records')->where('id', $sara)->update(['title' => 'Sara Ali']);
        $campaign = $this->operatingRecord('campaigns', 'running', ['objective' => 'leads', 'budget_cap' => 1000], 'Ramadan Offer');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Existing', 'lead_phone' => '01005550000', 'phone_key' => '005550000'], 'Existing');
        $csv = "Client Name,Phone,Lead Quality,Campaign Name,Stage,Sales Agent,Lead Time\n"
            ."Omar,01001112223,Hot,Ramadan Offer,Contacted,Sara Ali,2026-10-01 09:30\n"
            ."Mona,+20 100 555 0000,Warm,Ramadan Offer,New,,\n"
            ."Omar again,0100 111 2223,Cold,,,,\n"
            ."Hany,01007778889,ساخن,Unknown Campaign,Won,,\n";

        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('leads.csv', $csv)])->assertRedirect();
        $token = basename(collect(Storage::disk('local')->files('crm-imports'))->sole(), '.json');

        $this->actingAs($manager)->get("/admin/operations/crm/import/{$token}")->assertOk()->assertSee('Omar')->assertSee(__('مكرر'));

        $map = ['title', 'lead_phone', 'lead_quality', 'campaign', 'status', 'assignee', 'lead_date'];
        $this->actingAs($manager)->post("/admin/operations/crm/import/{$token}", ['map' => $map, 'source' => 'facebook', 'skip_sla' => '1'])->assertRedirect('/admin/operations/crm/import')->assertSessionHas('import_result');

        $result = session('import_result');
        $this->assertSame(2, $result['created']);
        $this->assertSame(2, $result['duplicates']);
        $this->assertSame([], $result['failed']);
        $omar = DB::table('operating_records')->where('type', 'opportunities')->where('title', 'Omar')->sole();
        $omarData = json_decode($omar->data, true);
        $this->assertSame(['contacted', 'hot', $campaign, $sara, 'facebook', '2026-10-01T09:30'], [$omar->status, $omarData['lead_quality'], $omarData['campaign_id'], $omarData['assignee_id'], $omarData['lead_source'], $omarData['lead_date']]);
        $this->assertEmpty($omarData['sla_due_at'] ?? null);
        $hany = DB::table('operating_records')->where('title', 'Hany')->sole();
        $this->assertSame('new', $hany->status);
        $this->assertSame('hot', json_decode($hany->data, true)['lead_quality']);
        $this->assertNotEmpty(array_filter($result['warnings'], fn (array $warning): bool => $warning['row'] === 5));
        $this->assertTrue(DB::table('operating_events')->where('action', 'import_leads')->exists());
        $this->assertSame([], Storage::disk('local')->files('crm-imports'));
    }

    public function test_reps_cannot_import_and_tokens_belong_to_their_uploader(): void
    {
        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name\nA\n")])->assertForbidden();

        $manager = $this->operatingUser('sales_manager');
        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name,Phone\nA,0100\n")]);
        $token = basename(collect(Storage::disk('local')->files('crm-imports'))->sole(), '.json');

        $this->actingAs($this->operatingUser('sales_manager'))->get("/admin/operations/crm/import/{$token}")->assertForbidden();
    }

    public function test_default_campaign_and_owner_must_be_records_the_manager_can_pick(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $project = $this->operatingRecord('projects', 'active', [], 'Secret project');
        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name,Phone\nA,01001230000\n")]);
        $token = basename(collect(Storage::disk('local')->files('crm-imports'))->sole(), '.json');

        $this->actingAs($manager)->get("/admin/operations/crm/import/{$token}?campaign_id={$project}")->assertSessionHasErrors('campaign_id');
        $this->actingAs($manager)->post("/admin/operations/crm/import/{$token}", ['map' => ['title', 'lead_phone'], 'source' => 'other', 'assignee_id' => $project])->assertSessionHasErrors('assignee_id');
    }

    public function test_oversized_or_unmapped_files_are_refused(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $rows = "Name,Phone\n".str_repeat("A,0100\n", 2001);

        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('big.csv', $rows)])->assertSessionHasErrors('workflow');

        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name,Phone\nA,0100\n")]);
        $token = basename(collect(Storage::disk('local')->files('crm-imports'))->sole(), '.json');
        $this->actingAs($manager)->post("/admin/operations/crm/import/{$token}", ['map' => ['country', null], 'source' => 'other'])->assertSessionHasErrors('map');
    }
}
