<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Show attendance settings form
     */
    public function attendanceSettings(Request $request): Response
    {
        // Authorization: Only admins can access settings
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized access.');

        $settings = [
            'office_start_time' => Setting::get('attendance.office_start_time', '09:30'),
            'late_grace_minutes' => Setting::get('attendance.late_grace_minutes', 15),
            'office_end_time' => Setting::get('attendance.office_end_time', '17:30'),
            'default_break_minutes' => Setting::get('attendance.default_break_minutes', 60),
        ];

        return Inertia::render('settings/Attendance', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update attendance settings
     */
    public function updateAttendanceSettings(Request $request): RedirectResponse
    {
        // Authorization: Only admins can update settings
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized access.');

        $validated = $request->validate([
            'office_start_time' => 'required|date_format:H:i',
            'late_grace_minutes' => 'required|integer|min:0|max:120',
            'office_end_time' => 'required|date_format:H:i',
            'default_break_minutes' => 'required|integer|min:0|max:240',
        ]);

        $userId = $request->user()->id;

        Setting::set('attendance.office_start_time', $validated['office_start_time'], $userId);
        Setting::set('attendance.late_grace_minutes', (string) $validated['late_grace_minutes'], $userId);
        Setting::set('attendance.office_end_time', $validated['office_end_time'], $userId);
        Setting::set('attendance.default_break_minutes', (string) $validated['default_break_minutes'], $userId);

        // Clear all settings cache
        Setting::clearCache();

        return redirect()->back()->with('success', 'Attendance settings updated successfully.');
    }
}
