<?php

namespace App\Console\Commands;

use App\Services\OperatingAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CloseAttendanceDay extends Command
{
    protected $signature = 'operating:attendance-close-day {--date= : Workday to close (Y-m-d), defaults to today}';

    protected $description = 'Record red points for active employees without check-in, mark approved leave as excused and alert on consecutive absences';

    public function handle(OperatingAttendance $attendance): int
    {
        $option = $this->option('date') ?: today()->toDateString();
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $option) ?: null;
        if (! $date || $date->toDateString() !== $option) {
            $this->error('The --date option must use the Y-m-d format, for example 2026-10-06.');

            return self::FAILURE;
        }
        $result = $attendance->closeDay($date);
        $this->info(sprintf('%s: %d absent, %d excused.', $date->toDateString(), count($result['absent']), count($result['excused'])));

        return self::SUCCESS;
    }
}
