<?php

namespace App\Console\Commands;

use App\Services\OperatingCrm;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CheckCrmSla extends Command
{
    protected $signature = 'operating:crm-sla';

    protected $description = 'Alert the owner, their manager and sales managers once when a new lead passes its response deadline uncontacted';

    public function handle(OperatingCrm $crm): int
    {
        $this->info($crm->sendSlaAlerts(CarbonImmutable::now()).' SLA alerts sent.');

        return self::SUCCESS;
    }
}
