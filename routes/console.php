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
