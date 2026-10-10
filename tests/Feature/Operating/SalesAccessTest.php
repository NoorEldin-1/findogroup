<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SalesAccessTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_sales_manager_is_a_valid_role_that_reads_and_writes_the_sales_group(): void
    {
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->get('/admin/operations/opportunities')->assertOk();
        $this->actingAs($manager)->post('/admin/operations/clients', ['title' => 'Acme', 'reason' => 'New client', 'data' => ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100']])->assertSessionHasNoErrors();
    }

    public function test_sales_sees_only_own_or_assigned_leads_while_the_manager_sees_all(): void
    {
        $sales = $this->operatingUser('sales');
        $salesEmployee = $this->employeeFor($sales, ['department' => 'sales']);
        $other = $this->operatingUser('sales');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Mine', 'assignee_id' => $salesEmployee], 'Assigned to me');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Created'], 'Created by me', $sales->id);
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Other'], 'Someone else', $other->id);

        $titles = collect($this->actingAs($sales)->getJson('/admin/operations/opportunities?format=json')->json('data'))->pluck('title')->sort()->values()->all();
        $this->assertSame(['Assigned to me', 'Created by me'], $titles);

        $this->assertCount(3, $this->actingAs($this->operatingUser('sales_manager'))->getJson('/admin/operations/opportunities?format=json')->json('data'));
    }

    public function test_sales_manager_can_archive_and_reads_projects_like_sales(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $clientId = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');
        $projectId = $this->operatingRecord('projects', 'discovery', ['client_id' => $clientId, 'outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');

        $this->actingAs($manager)->get("/admin/operations/record/{$projectId}")->assertOk();
        $this->actingAs($manager)->post("/admin/operations/record/{$clientId}/transition", ['target' => 'archived', 'version' => 1, 'reason' => 'Duplicate'])->assertSessionHasNoErrors();
    }
}
