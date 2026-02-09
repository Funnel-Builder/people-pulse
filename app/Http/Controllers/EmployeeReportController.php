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
    private function getReportData(User $employee)
    {
        $employee->load(['department', 'subDepartment', 'attendances', 'leaves.leaveType']);

        // 1. Personal Details
        $personalDetails = [
            'id' => $employee->id,
            'name' => $employee->name,
            'employee_id' => $employee->employee_id,
            'designation' => $employee->designation,
            'department' => $employee->department?->name,
            'sub_department' => $employee->subDepartment?->name,
            'joining_date' => $employee->joining_date ? $employee->joining_date->format('M d, Y') : null,
            'email' => $employee->email,
            'phone' => $employee->phone ?? 'N/A',
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
        $oneYearAgo = Carbon::today()->subMonths(11)->startOfMonth();
        $attendanceRecords = $employee->attendances()
            ->whereBetween('date', [$oneYearAgo->toDateString(), Carbon::today()->toDateString()])
            ->get();

        // Calculate Monthly Trends (PHP version of Vue logic)
        $monthlyTrends = [];
        $today = Carbon::today();

        // Initialize last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $d = $today->copy()->subMonths($i);
            $key = $d->format('Y-m');
            $monthlyTrends[$key] = [
                'monthLabel' => $d->format('M'),
                'year' => $d->year,
                'month' => $d->month,
                'totalMinutes' => 0,
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'totalEntries' => 0,
                'hours' => 0,
                'score' => 0,
                'workHeight' => 0,
                'punctualityHeight' => 0,
            ];
        }

        foreach ($attendanceRecords as $record) {
            $d = Carbon::parse($record->date);
            $key = $d->format('Y-m');

            if (isset($monthlyTrends[$key])) {
                if ($record->net_minutes) {
                    $monthlyTrends[$key]['totalMinutes'] += $record->net_minutes;
                } elseif ($record->clock_in && $record->clock_out) {
                    $start = Carbon::parse($record->clock_in);
                    $end = Carbon::parse($record->clock_out);
                    $monthlyTrends[$key]['totalMinutes'] += $end->diffInMinutes($start);
                }

                if ($record->status !== 'weekend' && $record->status !== 'holiday') {
                    $monthlyTrends[$key]['totalEntries']++;
                    if ($record->status === 'present' || $record->status === 'late') {
                        $monthlyTrends[$key]['present']++;
                    }
                    if ($record->is_late) {
                        $monthlyTrends[$key]['late']++;
                    }
                    if ($record->status === 'absent') {
                        $monthlyTrends[$key]['absent']++;
                    }
                }
            }
        }

        // Finalize calculations
        foreach ($monthlyTrends as &$m) {
            $m['hours'] = floor($m['totalMinutes'] / 60);

            // Score Calculation: 100 - (Absent * 10) - (Late * 2)
            $score = 0;
            if ($m['totalEntries'] > 0) {
                $score = 100 - ($m['absent'] * 10) - ($m['late'] * 2);
                $score = max(0, min(100, $score));
            }
            $m['score'] = round($score);

            // Work Hours Height
            $workHeight = ($m['hours'] / 200) * 100;
            $m['workHeight'] = max(15, min(100, $workHeight));

            // Performance Height
            $punctualityHeight = $score;
            $m['punctualityHeight'] = max(15, $punctualityHeight);
        }

        // 4. Leave Stats
        $leaveStats = [
            'total_leaves_taken' => $employee->leaves()->where('status', 'approved')->count(),
            'rejected_requests' => $employee->leaves()->where('status', 'rejected')->count(),
            'pending_requests' => $employee->leaves()->where('status', 'pending')->count(),
        ];

        // Detailed leave breakdown
        $leaveBreakdown = $employee->leaves()
            ->where('status', 'approved')
            ->with('leaveType')
            ->get()
            ->groupBy('leave_type_id')
            ->map(fn($leaves) => [
                'type' => $leaves->first()->leaveType->name,
                'count' => $leaves->count(),
            ])->values();

        // 5. Cover History
        $coverHistoryCount = Leave::where('cover_person_id', $employee->id)->count();
        $recentCovers = Leave::where('cover_person_id', $employee->id)
            ->with(['user'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($leave) => [
                'date' => $leave->created_at->format('M d, Y'),
                'covered_for' => $leave->user->name,
                'type' => $leave->type,
            ]);

        return [
            'employee' => $personalDetails,
            'attendanceStats' => $attendanceStats,
            'leaveStats' => $leaveStats,
            'leaveBreakdown' => $leaveBreakdown,
            'monthlyTrends' => array_values($monthlyTrends),
            'coverHistory' => [
                'count' => $coverHistoryCount,
                'recent' => $recentCovers,
            ],
            // 'attendanceHistory' is used only for chart in Vue, but we calculated trends here
            'attendanceHistory' => $attendanceRecords->map(fn($item) => [
                'date' => $item->date,
                'status' => $item->status,
                'clock_in' => $item->clock_in,
                'clock_out' => $item->clock_out,
                'is_late' => $item->is_late,
                'net_minutes' => $item->net_minutes,
            ]),
        ];
    }

    public function show(User $employee)
    {
        $data = $this->getReportData($employee);

        return Inertia::render('Reports/EmployeeLifetimeReport', [
            'employee' => $data['employee'],
            'attendanceStats' => $data['attendanceStats'],
            'attendanceHistory' => $data['attendanceHistory']->keyBy(fn($item) => Carbon::parse($item['date'])->format('Y-m-d')),
            'leaveStats' => $data['leaveStats'],
            'leaveBreakdown' => $data['leaveBreakdown'],
            'coverHistory' => $data['coverHistory'],
            // We can pass 'monthlyTrends' to Vue if we want to sync backend logic later
        ]);
    }

    public function export(Request $request, User $employee)
    {
        $data = $this->getReportData($employee);

        if ($request->has('preview')) {
            return view('pdf.employee-lifetime-report', $data);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.employee-lifetime-report', $data);

        return $pdf->stream("{$employee->name}_Lifetime_Report.pdf");
    }
}
