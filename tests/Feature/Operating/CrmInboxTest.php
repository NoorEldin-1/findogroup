<?php

namespace Tests\Feature\Operating;

use App\Notifications\OperatingAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmInboxTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_assigning_a_lead_to_someone_else_notifies_them_but_self_assignment_does_not(): void
    {
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser);

        $this->actingAs($this->operatingUser('sales_manager'))->post('/admin/operations/opportunities', ['title' => 'Nile Foods', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01001234567', 'lead_source' => 'facebook', 'assignee_id' => $rep]])->assertSessionHasNoErrors();
        $this->actingAs($repUser)->post('/admin/operations/opportunities', ['title' => 'Own lead', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01007654321', 'lead_source' => 'facebook']])->assertSessionHasNoErrors();

        $this->assertSame(1, $repUser->notifications()->count());
        $notification = $repUser->notifications()->first();
        $this->assertSame('lead_assigned', $notification->data['kind']);
        $this->assertStringStartsWith('/admin/operations/crm/leads/', $notification->data['url']);
    }

    public function test_tabs_filter_by_kind_and_show_counts(): void
    {
        $user = $this->operatingUser('sales');
        $user->notify(new OperatingAlert('crm_sla', ['ar' => 'SLA breach', 'en' => 'SLA breach'], ['ar' => 'Late lead', 'en' => 'Late lead'], '/x'));
        $user->notify(new OperatingAlert('lead_assigned', ['ar' => 'New lead', 'en' => 'New lead'], ['ar' => 'Omar', 'en' => 'Omar'], '/y'));

        $this->actingAs($user)->get('/admin/operations/notifications?tab=sla')->assertOk()->assertSee('Late lead')->assertDontSee('Omar');
        $this->actingAs($user)->get('/admin/operations/notifications?tab=leads')->assertSee('Omar')->assertDontSee('Late lead');
        $this->actingAs($user)->get('/admin/operations/notifications')->assertSee('Omar')->assertSee('Late lead');
    }
}
