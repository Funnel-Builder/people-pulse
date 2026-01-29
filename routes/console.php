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

// Mark absent employees at 10:30 AM (those without attendance records)
Schedule::command('attendance:mark-absent')->dailyAt('10:30');

// Send clock-in reminder at office start time (09:30)
Schedule::command('attendance:notify-missed-clockin')->dailyAt('09:30');

// Send clock-out reminder to employees at 17:30
Schedule::command('attendance:remind-clockout')->dailyAt('17:30');

// Notify admins about employees who missed clock-out at 18:30
Schedule::command('attendance:notify-missed-clockout')->dailyAt('18:30');

// Deactivate employees whose closing date has passed (runs at 00:01)
Schedule::command('employees:deactivate-separated')->dailyAt('00:01');
