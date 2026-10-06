<?php

namespace App\Console\Commands;

use App\Notifications\OperatingAlert;
use App\Services\OperatingAttendance;
use App\Services\OperatingNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendAttendanceMonthlyReport extends Command
{
    protected $signature = 'operating:attendance-monthly-report {--month= : Month (Y-m), defaults to the previous month}';

    protected $description = 'Send management and HR the monthly list of employees eligible for the attendance bonus';

    public function handle(OperatingAttendance $attendance, OperatingNotifier $notifier): int
    {
        $option = $this->option('month') ?: today()->subMonthNoOverflow()->format('Y-m');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $option)) {
            $this->error('The --month option must use the Y-m format, for example 2026-10.');

            return self::FAILURE;
        }
        $month = CarbonImmutable::createFromFormat('Y-m-d', $option.'-01');
        $rows = collect($attendance->monthSummary(null, $month->year, $month->month))->where('eligible', true);
        $currency = $attendance->rules()['currency'];
        $list = $rows->map(fn (array $row): string => $row['name'].' ('.$row['commitment'].'% → '.$row['bonus'].' '.$currency.')')->implode('، ');
        $notifier->send($notifier->roles(['owner', 'hr']), new OperatingAlert(
            'bonus_report',
            ['ar' => 'تقرير البونص الشهري '.$month->format('Y-m'), 'en' => 'Monthly bonus report '.$month->format('Y-m')],
            ['ar' => $rows->count().' مستحق، الإجمالي '.$rows->sum('bonus').' '.$currency.'. '.$list, 'en' => $rows->count().' eligible, total '.$rows->sum('bonus').' '.$currency.'. '.$list],
            '/admin/operations/attendance/monthly?month='.$month->format('Y-m'),
            true,
        ));
        $this->info($rows->count().' eligible employees reported.');

        return self::SUCCESS;
    }
}
