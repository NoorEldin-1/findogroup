<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MigrationsSmokeTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_owner_can_open_the_operating_overview(): void
    {
        $owner = $this->operatingUser('owner');

        $this->actingAs($owner)->get('/admin')->assertOk();
    }
}
