<?php

namespace App\Console\Commands;

use App\Services\OperatingFollowUps;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'operating:follow-up-reminders';

    protected $description = 'Remind owners of follow-ups due within the hour and escalate overdue ones to their manager and sales managers';

    public function handle(OperatingFollowUps $followUps): int
    {
        $sent = $followUps->sendReminders(CarbonImmutable::now());
        $this->info(sprintf('%d reminders, %d overdue alerts.', $sent['due'], $sent['overdue']));

        return self::SUCCESS;
    }
}
