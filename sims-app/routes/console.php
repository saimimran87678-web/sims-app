<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('whatsapp:process-queue')->everyMinute()->withoutOverlapping();

// Periodic background sync for licenses & telemetry heartbeat every 5 minutes
Schedule::call(function () {
    \App\Services\LicenseSyncService::syncBackground();
})->everyFiveMinutes()->name('sims-license-heartbeat')->withoutOverlapping();

// Daily SIMS Auto-Update & Maintenance at 02:00 AM
Schedule::command('sims:update')->dailyAt('02:00')->withoutOverlapping();

