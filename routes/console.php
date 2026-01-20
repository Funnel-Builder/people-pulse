<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Calculate attendance-based leave accrual daily at midnight
Schedule::command('leave:calculate-accrual')->dailyAt('00:00');

// Mark absent employees at 11 PM (those without attendance records)
Schedule::command('attendance:mark-absent')->dailyAt('13:55');
