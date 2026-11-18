<?php

use App\Jobs\SendOverdueTasksDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily digest of overdue tasks, sent at 07:00 server time
Schedule::job(new SendOverdueTasksDigest)->dailyAt('07:00');
