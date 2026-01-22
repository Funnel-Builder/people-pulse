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
Schedule::command('attendance:mark-absent')->dailyAt('23:30');

// Notify admins about employees who missed clock-out at 11:30 PM
Schedule::command('attendance:notify-missed-clockout')->dailyAt('18:30');

// Deactivate employees whose closing date has passed (runs at 00:01)
Schedule::command('employees:deactivate-separated')->dailyAt('00:01');
