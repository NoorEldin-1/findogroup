<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('website:publish-due')->everyMinute()->withoutOverlapping();
Schedule::command('operating:attendance-close-day')->dailyAt('23:55')->withoutOverlapping();
Schedule::command('operating:attendance-monthly-report')->monthlyOn(1, '08:00');
Schedule::command('operating:follow-up-reminders')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('operating:milestone-reminders')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('operating:procurement-alerts')->dailyAt('08:10')->withoutOverlapping();
Schedule::command('operating:asset-alerts')->dailyAt('08:15')->withoutOverlapping();
