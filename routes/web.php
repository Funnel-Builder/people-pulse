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
    Route::post('employees/{employee}/separate', [\App\Http\Controllers\EmployeeController::class, 'separate'])->name('employees.separate');

    // =====================================================
    // SETTINGS (Admin only)
    // =====================================================
    Route::prefix('settings')->group(function () {
        Route::get('/attendance', [App\Http\Controllers\Admin\AttendanceSettingController::class, 'index'])->name('settings.attendance');
        Route::post('/attendance', [App\Http\Controllers\Admin\AttendanceSettingController::class, 'update'])->name('settings.attendance.update');
        Route::get('/leaves', [App\Http\Controllers\Admin\LeaveSettingController::class, 'index'])->name('settings.leaves');

        // Holidays
        Route::resource('holidays', App\Http\Controllers\Settings\HolidayController::class)->except(['create', 'edit', 'show']);
    });

    // =====================================================
    // SERVICES MODULE
    // =====================================================
    Route::prefix('services')->name('services.')->group(function () {
        // Employee Certificate (Standard)
        Route::get('/employment-certificate/history', [\App\Http\Controllers\CertificateController::class, 'history'])->defaults('type', 'employment_certificate')->name('certificate.history');
        Route::get('/employment-certificate', [\App\Http\Controllers\CertificateController::class, 'index'])->name('certificate');
        Route::post('/employment-certificate', [\App\Http\Controllers\CertificateController::class, 'store'])->name('certificate.store');
        Route::get('/employment-certificate/{certificateRequest}/review', [\App\Http\Controllers\CertificateController::class, 'review'])->name('certificate.review');
        Route::post('/employment-certificate/{certificateRequest}/issue', [\App\Http\Controllers\CertificateController::class, 'issue'])->name('certificate.issue');
        Route::post('/employment-certificate/{certificateRequest}/reject', [\App\Http\Controllers\CertificateController::class, 'reject'])->name('certificate.reject');
        Route::post('/employment-certificate/{certificateRequest}/cancel', [\App\Http\Controllers\CertificateController::class, 'cancel'])->name('certificate.cancel');
        Route::post('/employment-certificate/{certificateRequest}/authorize', [\App\Http\Controllers\CertificateController::class, 'authorizeRequest'])->name('certificate.authorize');
        Route::get('/employment-certificate/{certificateRequest}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->name('certificate.download');
        Route::get('/employment-certificate/{certificateRequest}/preview', [\App\Http\Controllers\CertificateController::class, 'preview'])->name('certificate.preview');
        Route::post('/employment-certificate/{certificateRequest}/email', [\App\Http\Controllers\CertificateController::class, 'email'])->name('certificate.email');
        Route::post('/employment-certificate/{certificateRequest}/request-missing-info', [\App\Http\Controllers\CertificateController::class, 'requestMissingInfo'])->name('certificate.request-missing-info');

        // Visa Recommendation Letter
        Route::get('/visa-recommendation-letter/history', [\App\Http\Controllers\CertificateController::class, 'history'])->defaults('type', 'visa_recommendation_letter')->name('certificate.visa.history');
        Route::get('/visa-recommendation-letter', [\App\Http\Controllers\CertificateController::class, 'index'])->defaults('type', 'visa_recommendation_letter')->name('certificate.visa');
        Route::post('/visa-recommendation-letter', [\App\Http\Controllers\CertificateController::class, 'store'])->name('certificate.visa.store');
        Route::get('/visa-recommendation-letter/{certificateRequest}/review', [\App\Http\Controllers\CertificateController::class, 'review'])->name('certificate.visa.review');
        Route::post('/visa-recommendation-letter/{certificateRequest}/issue', [\App\Http\Controllers\CertificateController::class, 'issue'])->name('certificate.visa.issue');
        Route::post('/visa-recommendation-letter/{certificateRequest}/reject', [\App\Http\Controllers\CertificateController::class, 'reject'])->name('certificate.visa.reject');
        Route::post('/visa-recommendation-letter/{certificateRequest}/cancel', [\App\Http\Controllers\CertificateController::class, 'cancel'])->name('certificate.visa.cancel');
        Route::post('/visa-recommendation-letter/{certificateRequest}/authorize', [\App\Http\Controllers\CertificateController::class, 'authorizeRequest'])->name('certificate.visa.authorize');
        Route::get('/visa-recommendation-letter/{certificateRequest}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->name('certificate.visa.download');
        Route::get('/visa-recommendation-letter/{certificateRequest}/preview', [\App\Http\Controllers\CertificateController::class, 'preview'])->name('certificate.visa.preview');
        Route::post('/visa-recommendation-letter/{certificateRequest}/email', [\App\Http\Controllers\CertificateController::class, 'email'])->name('certificate.visa.email');
        Route::post('/visa-recommendation-letter/{certificateRequest}/request-missing-info', [\App\Http\Controllers\CertificateController::class, 'requestMissingInfo'])->name('certificate.visa.request-missing-info');

        // Release Letter
        Route::get('/release-letter/history', [\App\Http\Controllers\CertificateController::class, 'history'])->defaults('type', 'release_letter')->name('certificate.release.history');
        Route::get('/release-letter', [\App\Http\Controllers\CertificateController::class, 'index'])->defaults('type', 'release_letter')->name('certificate.release');
        Route::post('/release-letter', [\App\Http\Controllers\CertificateController::class, 'store'])->name('certificate.release.store');
        Route::get('/release-letter/{certificateRequest}/review', [\App\Http\Controllers\CertificateController::class, 'review'])->name('certificate.release.review');
        Route::post('/release-letter/{certificateRequest}/issue', [\App\Http\Controllers\CertificateController::class, 'issue'])->name('certificate.release.issue');
        Route::post('/release-letter/{certificateRequest}/reject', [\App\Http\Controllers\CertificateController::class, 'reject'])->name('certificate.release.reject');
        Route::post('/release-letter/{certificateRequest}/cancel', [\App\Http\Controllers\CertificateController::class, 'cancel'])->name('certificate.release.cancel');
        Route::post('/release-letter/{certificateRequest}/authorize', [\App\Http\Controllers\CertificateController::class, 'authorizeRequest'])->name('certificate.release.authorize');
        Route::get('/release-letter/{certificateRequest}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->name('certificate.release.download');
        Route::get('/release-letter/{certificateRequest}/preview', [\App\Http\Controllers\CertificateController::class, 'preview'])->name('certificate.release.preview');
        Route::post('/release-letter/{certificateRequest}/email', [\App\Http\Controllers\CertificateController::class, 'email'])->name('certificate.release.email');
        Route::post('/release-letter/{certificateRequest}/request-missing-info', [\App\Http\Controllers\CertificateController::class, 'requestMissingInfo'])->name('certificate.release.request-missing-info');

        // Experience Certificate
        Route::get('/experience-certificate/history', [\App\Http\Controllers\CertificateController::class, 'history'])->defaults('type', 'experience_certificate')->name('certificate.experience.history');
        Route::get('/experience-certificate', [\App\Http\Controllers\CertificateController::class, 'index'])->defaults('type', 'experience_certificate')->name('certificate.experience');
        Route::post('/experience-certificate', [\App\Http\Controllers\CertificateController::class, 'store'])->name('certificate.experience.store');
        Route::get('/experience-certificate/{certificateRequest}/review', [\App\Http\Controllers\CertificateController::class, 'review'])->name('certificate.experience.review');
        Route::post('/experience-certificate/{certificateRequest}/issue', [\App\Http\Controllers\CertificateController::class, 'issue'])->name('certificate.experience.issue');
        Route::post('/experience-certificate/{certificateRequest}/reject', [\App\Http\Controllers\CertificateController::class, 'reject'])->name('certificate.experience.reject');
        Route::post('/experience-certificate/{certificateRequest}/cancel', [\App\Http\Controllers\CertificateController::class, 'cancel'])->name('certificate.experience.cancel');
        Route::post('/experience-certificate/{certificateRequest}/authorize', [\App\Http\Controllers\CertificateController::class, 'authorizeRequest'])->name('certificate.experience.authorize');
        Route::get('/experience-certificate/{certificateRequest}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->name('certificate.experience.download');
        Route::get('/experience-certificate/{certificateRequest}/preview', [\App\Http\Controllers\CertificateController::class, 'preview'])->name('certificate.experience.preview');
        Route::post('/experience-certificate/{certificateRequest}/email', [\App\Http\Controllers\CertificateController::class, 'email'])->name('certificate.experience.email');
        Route::post('/experience-certificate/{certificateRequest}/request-missing-info', [\App\Http\Controllers\CertificateController::class, 'requestMissingInfo'])->name('certificate.experience.request-missing-info');
        
        Route::get('/approvals', [\App\Http\Controllers\CertificateController::class, 'approvals'])->name('certificate.approvals');
    });
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
