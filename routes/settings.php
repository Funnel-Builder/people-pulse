<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('settings/profile/picture', [ProfileController::class, 'removeProfilePicture'])->name('profile.picture.remove');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    // Admin Settings
    Route::get('settings/attendance', [\App\Http\Controllers\Admin\SettingController::class, 'attendanceSettings'])->name('settings.attendance');
    Route::post('settings/attendance', [\App\Http\Controllers\Admin\SettingController::class, 'updateAttendanceSettings'])->name('settings.attendance.update');

    // Leave Settings (Admin only)
    Route::get('settings/leaves', [\App\Http\Controllers\Admin\LeaveSettingController::class, 'index'])->name('settings.leaves');
    Route::get('settings/leaves/{user}/balances', [\App\Http\Controllers\Admin\LeaveSettingController::class, 'getEmployeeBalances'])->name('settings.leaves.balances');
    Route::post('settings/leaves/{user}/balances', [\App\Http\Controllers\Admin\LeaveSettingController::class, 'updateLeaveBalance'])->name('settings.leaves.update');

    // Department Settings
    // Department Settings
    Route::prefix('settings')->group(function () {
        Route::resource('departments', \App\Http\Controllers\Settings\DepartmentController::class)->except(['create', 'edit', 'show']);
        Route::post('departments/{department}/sub-departments', [\App\Http\Controllers\Settings\DepartmentController::class, 'storeSubDepartment'])->name('departments.sub-departments.store');
        Route::put('sub-departments/{subDepartment}', [\App\Http\Controllers\Settings\DepartmentController::class, 'updateSubDepartment'])->name('sub-departments.update');
        Route::delete('sub-departments/{subDepartment}', [\App\Http\Controllers\Settings\DepartmentController::class, 'destroySubDepartment'])->name('sub-departments.destroy');
    });

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');
});
