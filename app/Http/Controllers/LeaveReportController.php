<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class LeaveReportController extends Controller
{
    /**
     * Display leave report dashboard with stats and charts.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        
        // Get month/year from request or use current
        $selectedMonth = $request->input('month', $today->month);
        $selectedYear = $request->input('year', $today->year);
        
        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth();

        // Get employees based on role
        if ($user->isAdmin()) {
            $employeeIds = User::where('role', '!=', 'admin')->pluck('id')->toArray();
            $departments = Department::active()->withCount('users')->with('users')->get();
        } else {
            // Manager - only managed employees
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $employeeIds = User::whereIn('sub_department_id', $managedSubDepartmentIds)->pluck('id')->toArray();
            $departments = collect([]);
            if ($user->department_id) {
                $departments = Department::where('id', $user->department_id)->withCount('users')->with('users')->get();
            }
        }

        // Get leave types
        $leaveTypes = LeaveType::active()->get();

        // === SUMMARY STATS ===
        $totalLeaves = Leave::whereIn('user_id', $employeeIds)
            ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            })
            ->count();
        
        $approvedLeaves = Leave::whereIn('user_id', $employeeIds)
            ->where('status', Leave::STATUS_APPROVED)
            ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            })
            ->count();
        
        $pendingLeaves = Leave::whereIn('user_id', $employeeIds)
            ->where('status', Leave::STATUS_PENDING)
            ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            })
            ->count();
        
        $rejectedLeaves = Leave::whereIn('user_id', $employeeIds)
            ->where('status', Leave::STATUS_REJECTED)
            ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            })
            ->count();

        // Total leave days taken (approved)
        $totalLeaveDays = Leave::whereIn('user_id', $employeeIds)
            ->where('status', Leave::STATUS_APPROVED)
            ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            })
            ->withCount(['dates' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            }])
            ->get()
            ->sum('dates_count');

        // === CHART DATA ===

        // 1. Leave by Type (Pie Chart)
        $leaveByType = [];
        foreach ($leaveTypes as $leaveType) {
            $count = Leave::whereIn('user_id', $employeeIds)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', Leave::STATUS_APPROVED)
                ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                })
                ->count();
            
            if ($count > 0) {
                $leaveByType[] = [
                    'name' => $leaveType->name,
                    'code' => $leaveType->code,
                    'count' => $count,
                ];
            }
        }

        // 2. Monthly Trend (Bar Chart - last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $approved = Leave::whereIn('user_id', $employeeIds)
                ->where('status', Leave::STATUS_APPROVED)
                ->whereHas('dates', function ($query) use ($monthStart, $monthEnd) {
                    $query->whereBetween('date', [$monthStart, $monthEnd]);
                })
                ->count();
            
            $pending = Leave::whereIn('user_id', $employeeIds)
                ->where('status', Leave::STATUS_PENDING)
                ->whereHas('dates', function ($query) use ($monthStart, $monthEnd) {
                    $query->whereBetween('date', [$monthStart, $monthEnd]);
                })
                ->count();
            
            $monthlyTrend[] = [
                'month' => $monthStart->format('M'),
                'year' => $monthStart->format('Y'),
                'approved' => $approved,
                'pending' => $pending,
            ];
        }

        // 3. Department Distribution
        $departmentDistribution = [];
        foreach ($departments as $dept) {
            $deptEmployeeIds = $dept->users->pluck('id')->toArray();
            if (empty($deptEmployeeIds)) continue;
            
            $total = Leave::whereIn('user_id', $deptEmployeeIds)
                ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                })
                ->count();
            
            $approved = Leave::whereIn('user_id', $deptEmployeeIds)
                ->where('status', Leave::STATUS_APPROVED)
                ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                })
                ->count();
            
            $departmentDistribution[] = [
                'name' => $dept->name,
                'total_employees' => count($deptEmployeeIds),
                'total_leaves' => $total,
                'approved' => $approved,
            ];
        }

        // 4. Top Leave Takers
        $topLeaveTakers = Leave::whereIn('user_id', $employeeIds)
            ->where('status', Leave::STATUS_APPROVED)
            ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            })
            ->selectRaw('user_id, count(*) as leave_count')
            ->groupBy('user_id')
            ->orderByDesc('leave_count')
            ->limit(5)
            ->with('user:id,name,employee_id')
            ->get();

        // 5. Pending Approvals List
        $pendingApprovalsList = Leave::whereIn('user_id', $employeeIds)
            ->where('status', Leave::STATUS_PENDING)
            ->with(['user:id,name,employee_id', 'leaveType:id,name,code', 'dates'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'user' => $leave->user,
                    'leave_type' => $leave->leaveType->name,
                    'days' => $leave->dates->count(),
                    'start_date' => $leave->dates->min('date')?->format('M d, Y'),
                    'created_at' => $leave->created_at->format('M d, Y'),
                ];
            });

        // 6. Status Breakdown (Donut)
        $statusBreakdown = [
            'approved' => $approvedLeaves,
            'pending' => $pendingLeaves,
            'rejected' => $rejectedLeaves,
            'total' => $totalLeaves,
        ];

        return Inertia::render('Reports/LeaveReport', [
            'stats' => [
                'total_leaves' => $totalLeaves,
                'approved_leaves' => $approvedLeaves,
                'pending_leaves' => $pendingLeaves,
                'rejected_leaves' => $rejectedLeaves,
                'total_leave_days' => $totalLeaveDays,
            ],
            'charts' => [
                'leave_by_type' => $leaveByType,
                'monthly_trend' => $monthlyTrend,
                'department_distribution' => $departmentDistribution,
                'status_breakdown' => $statusBreakdown,
            ],
            'lists' => [
                'top_leave_takers' => $topLeaveTakers,
                'pending_approvals' => $pendingApprovalsList,
            ],
            'filters' => [
                'month' => (int) $selectedMonth,
                'year' => (int) $selectedYear,
            ],
        ]);
    }

    /**
     * Display employee leave summaries list.
     */
    public function employeeReport(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        $selectedMonth = $request->input('month', $today->month);
        $selectedYear = $request->input('year', $today->year);
        
        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth();

        // Get employees based on role
        if ($user->isAdmin()) {
            $employees = User::where('role', '!=', 'admin')
                ->with(['department:id,name', 'subDepartment:id,name'])
                ->orderBy('name')
                ->get();
        } else {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $employees = User::whereIn('sub_department_id', $managedSubDepartmentIds)
                ->with(['department:id,name', 'subDepartment:id,name'])
                ->orderBy('name')
                ->get();
        }

        // Calculate leave summary for each employee
        $employeeSummaries = $employees->map(function ($employee) use ($startOfMonth, $endOfMonth) {
            $leaves = Leave::where('user_id', $employee->id)
                ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                })
                ->with(['dates' => function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                }])
                ->get();
            
            $totalDays = $leaves->sum(fn($leave) => $leave->dates->count());
            $approvedDays = $leaves->where('status', Leave::STATUS_APPROVED)->sum(fn($leave) => $leave->dates->count());
            $pendingCount = $leaves->where('status', Leave::STATUS_PENDING)->count();
            $rejectedCount = $leaves->where('status', Leave::STATUS_REJECTED)->count();
            
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department_name' => $employee->department?->name,
                'sub_department_name' => $employee->subDepartment?->name,
                'designation' => $employee->designation,
                'total_leaves' => $leaves->count(),
                'total_days' => $totalDays,
                'approved_days' => $approvedDays,
                'pending_count' => $pendingCount,
                'rejected_count' => $rejectedCount,
            ];
        });

        // Get available years for filter
        $availableYears = range(Carbon::now()->year, Carbon::now()->year - 4);

        return Inertia::render('Reports/LeaveEmployeeReport', [
            'employees' => $employees->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'employee_id' => $e->employee_id]),
            'employeeSummaries' => $employeeSummaries,
            'filters' => [
                'month' => (int) $selectedMonth,
                'year' => (int) $selectedYear,
            ],
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Display individual employee leave details.
     */
    public function employeeDetail(Request $request, User $employee): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        // For managers, verify they can view this employee
        if ($user->isManager()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            if (!in_array($employee->sub_department_id, $managedSubDepartmentIds)) {
                abort(403, 'You do not have permission to view this employee.');
            }
        }

        $today = Carbon::today();
        $selectedMonth = $request->input('month', $today->month);
        $selectedYear = $request->input('year', $today->year);
        
        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth();

        // Get employee with department info
        $employee->load(['department:id,name', 'subDepartment:id,name']);

        // Get all employees for dropdown
        if ($user->isAdmin()) {
            $allEmployees = User::where('role', '!=', 'admin')
                ->select('id', 'name', 'employee_id')
                ->orderBy('name')
                ->get();
        } else {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $allEmployees = User::whereIn('sub_department_id', $managedSubDepartmentIds)
                ->select('id', 'name', 'employee_id')
                ->orderBy('name')
                ->get();
        }

        // Get leave records for the selected period
        $leaves = Leave::where('user_id', $employee->id)
            ->whereHas('dates', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
            })
            ->with(['leaveType:id,name,code', 'dates' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('date', [$startOfMonth, $endOfMonth])->orderBy('date');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'type' => $leave->type,
                    'leave_type' => $leave->leaveType->name,
                    'leave_type_code' => $leave->leaveType->code,
                    'status' => $leave->status,
                    'reason' => $leave->reason,
                    'days' => $leave->dates->count(),
                    'start_date' => $leave->dates->first()?->date?->format('Y-m-d'),
                    'end_date' => $leave->dates->last()?->date?->format('Y-m-d'),
                    'created_at' => $leave->created_at->format('Y-m-d'),
                ];
            });

        // Summary stats
        $summary = [
            'totalLeaves' => $leaves->count(),
            'totalDays' => $leaves->sum('days'),
            'approvedDays' => $leaves->where('status', Leave::STATUS_APPROVED)->sum('days'),
            'pendingCount' => $leaves->where('status', Leave::STATUS_PENDING)->count(),
            'rejectedCount' => $leaves->where('status', Leave::STATUS_REJECTED)->count(),
        ];

        // Get available years for filter
        $availableYears = range(Carbon::now()->year, Carbon::now()->year - 4);

        return Inertia::render('Reports/LeaveEmployeeDetail', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee_id,
                'department_name' => $employee->department?->name,
                'sub_department_name' => $employee->subDepartment?->name,
                'designation' => $employee->designation,
            ],
            'employees' => $allEmployees,
            'leaves' => $leaves,
            'summary' => $summary,
            'filters' => [
                'month' => (int) $selectedMonth,
                'year' => (int) $selectedYear,
            ],
            'availableYears' => $availableYears,
        ]);
    }
}
