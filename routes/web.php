<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');

    // Attendance routes
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'userDashboard'])->name('user');
        Route::get('/manager', [AttendanceController::class, 'managerDashboard'])->name('manager');
        Route::get('/manager/analytics', [AttendanceController::class, 'managerAnalytics'])->name('manager.analytics');
        Route::get('/admin', [AttendanceController::class, 'adminDashboard'])->name('admin');
        Route::get('/admin/analytics', [AttendanceController::class, 'adminAnalytics'])->name('admin.analytics');
        Route::get('/export', [AttendanceController::class, 'export'])->name('export');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/employee-report', [AttendanceController::class, 'employeeReport'])->name('employee-report');
        Route::get('/employee-report/export', [AttendanceController::class, 'exportEmployeeReport'])->name('employee-report.export');
        Route::get('/employee-report/{employee}', [AttendanceController::class, 'employeeAttendanceDetail'])->name('employee-report.detail');
        Route::get('/employee-report/{employee}/export', [AttendanceController::class, 'exportEmployeeAttendanceDetail'])->name('employee-report.detail.export');

        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock-out');

        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
        Route::patch('/{attendance}/override', [AttendanceController::class, 'override'])->name('override');
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');
    });

    // Leave Management routes
    Route::prefix('leaves')->name('leaves.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LeaveController::class, 'index'])->name('index');
        Route::get('/apply', [\App\Http\Controllers\LeaveController::class, 'create'])->name('create');
        Route::post('/advance', [\App\Http\Controllers\LeaveController::class, 'storeAdvance'])->name('store.advance');
        Route::post('/post', [\App\Http\Controllers\LeaveController::class, 'storePost'])->name('store.post');
        Route::get('/requests', [\App\Http\Controllers\LeaveController::class, 'requests'])->name('requests');
        Route::get('/approvals', [\App\Http\Controllers\LeaveController::class, 'approvals'])->name('approvals');
        Route::get('/{leave}', [\App\Http\Controllers\LeaveController::class, 'show'])->name('show');
        Route::post('/{leave}/process', [\App\Http\Controllers\LeaveApprovalController::class, 'process'])->name('process');
        Route::post('/{leave}/cancel', [\App\Http\Controllers\LeaveController::class, 'cancel'])->name('cancel');
    });

    // Employee Management routes (Admin only)
    Route::resource('employees', \App\Http\Controllers\EmployeeController::class)->except(['show']);
});

require __DIR__ . '/settings.php';
