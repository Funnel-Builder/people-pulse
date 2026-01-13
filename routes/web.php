<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\LeaveRecordsController;
use App\Http\Controllers\LeaveReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

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
});

require __DIR__ . '/settings.php';
