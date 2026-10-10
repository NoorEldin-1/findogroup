<?php

namespace App\Console\Commands;

use App\Services\OperatingProcurement;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendProcurementAlerts extends Command
{
    protected $signature = 'operating:procurement-alerts';

    protected $description = 'Alert procurement managers about late deliveries and supplier invoices that are due soon or overdue';

    public function handle(OperatingProcurement $procurement): int
    {
        $sent = $procurement->sendAlerts(CarbonImmutable::now());
        $this->info(sprintf('%d late deliveries, %d invoices due, %d invoices overdue.', $sent['late_deliveries'], $sent['invoices_due'], $sent['invoices_overdue']));

        return self::SUCCESS;
    }
}
