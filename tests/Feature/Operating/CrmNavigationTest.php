
namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmNavigationTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_reps_get_the_crm_section_without_manager_links(): void
    {
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin')->assertOk()
            ->assertSee('href="/admin/operations/crm"', false)->assertSee('href="/admin/operations/crm/leads"', false)->assertSee('href="/admin/operations/crm/calendar"', false)
            ->assertDontSee('href="/admin/operations/crm/team"', false)->assertDontSee('href="/admin/operations/crm/import"', false)->assertDontSee('href="/admin/operations/audit-log"', false);
    }

    public function test_managers_get_every_crm_link_and_other_roles_none(): void
    {
        $this->actingAs($this->operatingUser('sales_manager'))->get('/admin')->assertOk()
            ->assertSee('href="/admin/operations/crm/team"', false)->assertSee('href="/admin/operations/crm/import"', false)->assertSee('href="/admin/operations/crm/trash"', false)->assertSee('href="/admin/operations/audit-log"', false)->assertSee('href="/admin/operations/crm/settings"', false);
        $this->actingAs($this->operatingUser('designer'))->get('/admin')->assertOk()->assertDontSee('href="/admin/operations/crm"', false);
    }
}
