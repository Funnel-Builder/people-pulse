<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class EmployeeReportController extends Controller
{
    /**
     * Display a listing of employees for report selection.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['department', 'subDepartment'])
            ->where('role', '!=', 'admin') // or exclude admin if needed, usually we report on employees
            ->orderBy('joining_date', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $departmentId = $request->input('department_id');

        if ($departmentId === 'separated') {
            $query->where('is_active', false);
        } else {
            // Default to active only for all other views
            $query->where('is_active', true);

            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
        }

        $employees = $query->paginate(12)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'profile_picture' => $user->profile_picture,
                'designation' => $user->designation,
                'department' => $user->department?->name,
                'joining_date' => $user->joining_date ? $user->joining_date->format('M d, Y') : 'N/A',
                'is_active' => $user->isActive(),
            ]);

        // Get departments for filter and add Separated option
        $departments = \App\Models\Department::select('id', 'name')->get()
            ->map(fn($dept) => ['id' => (string) $dept->id, 'name' => $dept->name])
            ->push(['id' => 'separated', 'name' => 'Separated']);

        return Inertia::render('Reports/EmployeeReportIndex', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $request->only(['search', 'department_id']),
        ]);
    }

    /**
     * Display the specified employee's lifetime report.
     */
    public function show(User $employee)
    {
        $employee->load(['department', 'subDepartment', 'attendances', 'leaves.leaveType']);

        // 1. Personal Details
        $personalDetails = [
            'name' => $employee->name,
            'employee_id' => $employee->employee_id,
            'designation' => $employee->designation,
            'department' => $employee->department?->name,
            'sub_department' => $employee->subDepartment?->name,
            'joining_date' => $employee->joining_date ? $employee->joining_date->format('M d, Y') : null,
            'email' => $employee->email,
            'phone' => $employee->phone ?? 'N/A', // Assuming phone exists or add migration
            'profile_picture' => $employee->profile_picture,
            'status' => $employee->isActive() ? 'Active' : 'Inactive',
        ];

        // 2. Attendance Stats (Lifetime)
        $attendanceStats = [
            'total_present' => $employee->attendances()->where('status', 'present')->count(),
            'total_late' => $employee->attendances()->where('is_late', true)->count(),
            'total_absent' => $employee->attendances()->where('status', 'absent')->count(),
            'avg_working_hours' => number_format(($employee->attendances()->avg('net_minutes') ?? 0) / 60, 2),
        ];

        // 3. Attendance History for Charts (Last 12 Months)
        // We fetch raw attendance records to process on frontend for consistency with Dashboard
        $oneYearAgo = Carbon::today()->subMonths(11)->startOfMonth();
        $attendanceHistory = $employee->attendances()
            ->whereBetween('date', [$oneYearAgo->toDateString(), Carbon::today()->toDateString()])
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'))
            ->map(fn($item) => [
                'date' => $item->date,
                'status' => $item->status,
                'clock_in' => $item->clock_in,
                'clock_out' => $item->clock_out,
                'is_late' => $item->is_late,
                'net_minutes' => $item->net_minutes,
            ]);

        // 3. Leave Stats
        $leaveStats = [
            'total_leaves_taken' => $employee->leaves()->where('status', 'approved')->count(), // Days count would be better if we sum leave dates
            'rejected_requests' => $employee->leaves()->where('status', 'rejected')->count(),
            'pending_requests' => $employee->leaves()->where('status', 'pending')->count(),
        ];

        // Detailed leave breakdown by type
        $leaveBreakdown = $employee->leaves()
            ->where('status', 'approved')
            ->with('leaveType')
            ->get()
            ->groupBy('leave_type_id')
            ->map(fn($leaves) => [
                'type' => $leaves->first()->leaveType->name,
                'count' => $leaves->count(), // Simplify to just count requests for now, ideally sum days
            ])->values();

        // 4. Cover History (Times this employee covered for others)
        // Adjusting to count 'cover_person_id' in Leave model
        $coverHistoryCount = Leave::where('cover_person_id', $employee->id)->count();
        $recentCovers = Leave::where('cover_person_id', $employee->id)
            ->with(['user']) // The person who requested leave
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($leave) => [
                'date' => $leave->created_at->format('M d, Y'), // Or leave dates
                'covered_for' => $leave->user->name,
                'type' => $leave->type,
            ]);

        return Inertia::render('Reports/EmployeeLifetimeReport', [
            'employee' => $personalDetails,
            'attendanceStats' => $attendanceStats,
            'attendanceHistory' => $attendanceHistory,
            'leaveStats' => $leaveStats,
            'leaveBreakdown' => $leaveBreakdown,
            'coverHistory' => [
                'count' => $coverHistoryCount,
                'recent' => $recentCovers,
            ],
        ]);
    }

    public function export(User $employee)
    {
        // Implementation for export PDF/Excel
        // For now placeholder or reuse existing logic
        // This can be done via frontend "Print" button which is easier for "good UI/UX" reports
        return back();
    }
}
