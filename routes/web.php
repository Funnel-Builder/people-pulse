<?php

use App\Http\Controllers\TenantLookupController;
use App\Http\Controllers\TenantRegistrationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Central Routes (Landlord)
|--------------------------------------------------------------------------
|
| These routes are for the central/landlord application.
| They handle tenant lookup, registration, and the landing page.
| They should ideally only be accessible on central domains.
|
*/

// Middleware to ensure we are on a central domain could be added here
// Route::middleware(['web', 'central'])->group(function() { ... });

// Limit central routes to the central domain only
$centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

Route::domain($centralDomain)->group(function () {
    // Landing page
    Route::get('/', function () {
        return Inertia::render('Welcome');
    })->name('home');

    // Tenant lookup (Enter Office ID)
    Route::get('/login', [TenantLookupController::class, 'show'])->name('login');
    Route::post('/login', [TenantLookupController::class, 'lookup'])->name('tenant.lookup');

    // Tenant registration
    Route::get('/register', [TenantRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [TenantRegistrationController::class, 'store'])->name('register.store');
});
