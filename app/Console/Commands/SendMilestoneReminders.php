<?php

namespace App\Console\Commands;

use App\Services\OperatingMilestones;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendMilestoneReminders extends Command
{
    protected $signature = 'operating:milestone-reminders';

    protected $description = 'Alert project managers and owners about milestones due soon or overdue, and about overdue deliverables';

    public function handle(OperatingMilestones $milestones): int
    {
        $sent = $milestones->sendReminders(CarbonImmutable::now());
        $this->info(sprintf('%d upcoming, %d overdue, %d deliverables.', $sent['upcoming'], $sent['overdue'], $sent['deliverables']));

        return self::SUCCESS;
    }
}
