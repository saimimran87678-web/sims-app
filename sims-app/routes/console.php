<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('whatsapp:process-queue')->everyMinute()->withoutOverlapping();

// Daily background sync for licenses at midnight ("every day starting")
Schedule::call(function () {
    \App\Services\LicenseSyncService::syncBackground();
})->daily();
