<?php

namespace App\Console\Commands;

use App\Services\OperatingAssets;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendAssetAlerts extends Command
{
    protected $signature = 'operating:asset-alerts';

    protected $description = 'Alert asset managers about low stock and asset warranties ending within 30 days';

    public function handle(OperatingAssets $assets): int
    {
        $sent = $assets->sendAlerts(CarbonImmutable::now());
        $this->info(sprintf('%d low stock, %d warranty.', $sent['low_stock'], $sent['warranty']));

        return self::SUCCESS;
    }
}
