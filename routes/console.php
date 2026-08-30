<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Only fire if the host's cron actually calls `schedule:run` (see
// docs/DEPLOYMENT.md) — on a host with no Scheduled Tasks at all, these
// simply never run, same as before this existed.
Schedule::command('imports:clean-pending')->dailyAt('03:00');
Schedule::command('db:backup')->dailyAt('03:10');
