<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\AttendanceAdjustmentRequest;
use App\Policies\AttendanceAdjustmentRequestPolicy;
use App\Policies\AttendancePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Execute this globally before middleware stack runs
        $host = request()->getHost();
        // Dynamically compute safe cookie keys using the full domain string
        $cookieName = str_replace(['.', '-'], '_', $host) . '_session';
        Config::set('session.cookie', $cookieName);
        Config::set('session.domain', $host);
        // Register policies
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(AttendanceAdjustmentRequest::class, AttendanceAdjustmentRequestPolicy::class);
    }
}
