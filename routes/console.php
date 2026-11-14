<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:expire-online')->hourly();
Schedule::command('payments:reconcile')->everyTenMinutes();
Schedule::command('couriers:sync-steadfast')->everyThirtyMinutes();
