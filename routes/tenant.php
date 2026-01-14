<?php

declare(strict_types=1);

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\LeaveRecordsController;
use App\Http\Controllers\LeaveReportController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Middleware\InitializeTenancyByRouteParameter;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are for tenant-specific functionality.
| All routes are prefixed with /app/{tenant}
| Example: /app/abc-corp/dashboard
|
*/

Route::middleware([
    'web',
    InitializeTenancyByRouteParameter::class,
])->prefix('/app/{tenant}')->group(function () {

    // =====================================================
    // GUEST ROUTES (Login/Register for Tenant)
    // =====================================================
    Route::middleware('guest')->group(function () {
        Route::get('login', function () {
            return Inertia::render('Auth/Login');
        })->name('tenant.login');

        Route::post('login', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login');
    });

    // =====================================================
    // AUTHENTICATED ROUTES
    // =====================================================
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');

        // =====================================================
        // ATTENDANCE MODULE
        // =====================================================
        Route::prefix('attendance')->name('attendance.')->group(function () {
            // User views
            Route::get('/', [AttendanceController::class, 'userDashboard'])->name('user');
            Route::get('/manager', [AttendanceController::class, 'managerDashboard'])->name('manager');

            // Clock actions
            Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock-in');
            Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock-out');

            // Individual attendance record
            Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
            Route::patch('/{attendance}/override', [AttendanceController::class, 'override'])->name('override');
            Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');
        });

        // =====================================================
        // LEAVE MODULE
        // =====================================================
        Route::prefix('leaves')->name('leaves.')->group(function () {
            // User views
            Route::get('/', [\App\Http\Controllers\LeaveController::class, 'index'])->name('index');
            Route::get('/apply', [\App\Http\Controllers\LeaveController::class, 'create'])->name('create');
            Route::post('/advance', [\App\Http\Controllers\LeaveController::class, 'storeAdvance'])->name('store.advance');
            Route::post('/post', [\App\Http\Controllers\LeaveController::class, 'storePost'])->name('store.post');
            Route::get('/requests', [\App\Http\Controllers\LeaveController::class, 'requests'])->name('requests');
            Route::get('/approvals', [\App\Http\Controllers\LeaveController::class, 'approvals'])->name('approvals');

            // Individual leave record
            Route::get('/{leave}', [\App\Http\Controllers\LeaveController::class, 'show'])->name('show');
            Route::post('/{leave}/process', [\App\Http\Controllers\LeaveApprovalController::class, 'process'])->name('process');
            Route::post('/{leave}/cancel', [\App\Http\Controllers\LeaveController::class, 'cancel'])->name('cancel');
        });

        // =====================================================
        // RECORDS (Admin/Manager)
        // =====================================================
        Route::prefix('records')->name('records.')->group(function () {
            // Attendance Records
            Route::get('/attendance', [AttendanceController::class, 'adminDashboard'])->name('attendance');
            Route::get('/attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');

            // Leave Records
            Route::get('/leave', [LeaveRecordsController::class, 'index'])->name('leave');
            Route::get('/leave/export', [LeaveRecordsController::class, 'export'])->name('leave.export');
            Route::get('/leave/{leave}/manage', [LeaveRecordsController::class, 'manage'])->name('leave.manage');
        });

        // =====================================================
        // REPORTS (Admin/Manager)
        // =====================================================
        Route::prefix('reports')->name('reports.')->group(function () {
            // Attendance Reports
            Route::get('/attendance', [AttendanceReportController::class, 'index'])->name('attendance');
            Route::get('/attendance/export', [AttendanceReportController::class, 'export'])->name('attendance.export');
            Route::get('/attendance/employees', [AttendanceController::class, 'employeeReport'])->name('attendance.employees');
            Route::get('/attendance/employees/export', [AttendanceController::class, 'exportEmployeeReport'])->name('attendance.employees.export');
            Route::get('/attendance/employees/{employee}', [AttendanceController::class, 'employeeAttendanceDetail'])->name('attendance.employees.detail');
            Route::get('/attendance/employees/{employee}/export', [AttendanceController::class, 'exportEmployeeAttendanceDetail'])->name('attendance.employees.detail.export');

            // Leave Reports
            Route::get('/leave', [LeaveReportController::class, 'index'])->name('leave');
            Route::get('/leave/employees', [LeaveReportController::class, 'employeeReport'])->name('leave.employees');
            Route::get('/leave/employees/{employee}', [LeaveReportController::class, 'employeeDetail'])->name('leave.employees.detail');
        });

        // =====================================================
        // EMPLOYEE MANAGEMENT (Admin only)
        // =====================================================
        Route::resource('employees', \App\Http\Controllers\EmployeeController::class)->except(['show']);

        // =====================================================
        // ANNOUNCEMENTS (Admin/Manager)
        // =====================================================
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\AnnouncementController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('store');
            Route::get('/{announcement}/edit', [\App\Http\Controllers\AnnouncementController::class, 'edit'])->name('edit');
            Route::put('/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'update'])->name('update');
            Route::delete('/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'destroy'])->name('destroy');
            Route::post('/{announcement}/toggle', [\App\Http\Controllers\AnnouncementController::class, 'toggle'])->name('toggle');
        });

        // =====================================================
        // SETTINGS
        // =====================================================
        Route::redirect('settings', 'settings/profile');

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

        Route::get('settings/appearance', function () {
            return Inertia::render('settings/Appearance');
        })->name('appearance.edit');

        // Logout
        Route::post('logout', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy'])->name('tenant.logout');
    });
});
