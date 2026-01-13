<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class LeaveRecordsController extends Controller
{
    /**
     * Display all leave records for admin/manager.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        // Get filter values
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $leaveTypeId = $request->input('leave_type');
        $subDepartmentId = $request->input('sub_department');
        $employeeId = $request->input('employee');

        // Build query
        $query = Leave::with([
            'user:id,name,employee_id,department_id,sub_department_id,designation',
            'user.department:id,name',
            'user.subDepartment:id,name',
            'leaveType:id,name,code',
            'dates',
            'coverPerson:id,name',
        ]);

        // Filter by role permissions
        if ($user->isManager()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $query->whereHas('user', function ($q) use ($managedSubDepartmentIds) {
                $q->whereIn('sub_department_id', $managedSubDepartmentIds);
            });
        }

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereHas('dates', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        } elseif ($startDate) {
            $query->whereHas('dates', function ($q) use ($startDate) {
                $q->where('date', '>=', $startDate);
            });
        } elseif ($endDate) {
            $query->whereHas('dates', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate);
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($leaveTypeId && $leaveTypeId !== 'all') {
            $query->where('leave_type_id', $leaveTypeId);
        }

        if ($subDepartmentId && $subDepartmentId !== 'all_sub_departments') {
            $query->whereHas('user', function ($q) use ($subDepartmentId) {
                $q->where('sub_department_id', $subDepartmentId);
            });
        }

        if ($employeeId && $employeeId !== 'all_employees') {
            $query->where('user_id', $employeeId);
        }

        // Get paginated results
        $leaves = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Transform leaves for frontend
        $leaves->through(function ($leave) {
            return [
                'id' => $leave->id,
                'user' => $leave->user,
                'leave_type' => $leave->leaveType,
                'type' => $leave->type,
                'status' => $leave->status,
                'reason' => $leave->reason,
                'cover_person' => $leave->coverPerson,
                'days' => $leave->dates->count(),
                'start_date' => $leave->dates->min('date')?->format('Y-m-d'),
                'end_date' => $leave->dates->max('date')?->format('Y-m-d'),
                'created_at' => $leave->created_at->format('Y-m-d H:i'),
            ];
        });

        // Get filter options
        $leaveTypes = LeaveType::active()->get(['id', 'name', 'code']);
        $subDepartments = SubDepartment::with('department:id,name')->get(['id', 'name', 'department_id']);
        
        if ($user->isAdmin()) {
            $employees = User::where('role', '!=', 'admin')
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id']);
        } else {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $employees = User::whereIn('sub_department_id', $managedSubDepartmentIds)
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id']);
        }

        // Summary stats for today
        $today = Carbon::today();
        $employeeIds = $employees->pluck('id')->toArray();
        
        $summary = [
            'total' => Leave::whereIn('user_id', $employeeIds)->count(),
            'pending' => Leave::whereIn('user_id', $employeeIds)->where('status', Leave::STATUS_PENDING)->count(),
            'approved' => Leave::whereIn('user_id', $employeeIds)->where('status', Leave::STATUS_APPROVED)->count(),
            'rejected' => Leave::whereIn('user_id', $employeeIds)->where('status', Leave::STATUS_REJECTED)->count(),
        ];

        return Inertia::render('leaves/Records', [
            'leaves' => $leaves,
            'leaveTypes' => $leaveTypes,
            'subDepartments' => $subDepartments,
            'employees' => $employees,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'status' => $status ?? '',
                'leave_type' => $leaveTypeId ?? '',
                'sub_department' => $subDepartmentId ?? '',
                'employee' => $employeeId ?? '',
            ],
        ]);
    }

    /**
     * Export leave records to CSV/XLSX.
     */
    public function export(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        // Get filter values
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $leaveTypeId = $request->input('leave_type');
        $subDepartmentId = $request->input('sub_department');
        $employeeId = $request->input('employee');
        $type = $request->input('type', 'csv');

        // Build query
        $query = Leave::with([
            'user:id,name,employee_id,department_id,sub_department_id,designation',
            'user.department:id,name',
            'user.subDepartment:id,name',
            'leaveType:id,name,code',
            'dates',
            'coverPerson:id,name',
        ]);

        // Filter by role permissions
        if ($user->isManager()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $query->whereHas('user', function ($q) use ($managedSubDepartmentIds) {
                $q->whereIn('sub_department_id', $managedSubDepartmentIds);
            });
        }

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereHas('dates', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        } elseif ($startDate) {
            $query->whereHas('dates', function ($q) use ($startDate) {
                $q->where('date', '>=', $startDate);
            });
        } elseif ($endDate) {
            $query->whereHas('dates', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate);
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($leaveTypeId && $leaveTypeId !== 'all') {
            $query->where('leave_type_id', $leaveTypeId);
        }

        if ($subDepartmentId && $subDepartmentId !== 'all_sub_departments') {
            $query->whereHas('user', function ($q) use ($subDepartmentId) {
                $q->where('sub_department_id', $subDepartmentId);
            });
        }

        if ($employeeId && $employeeId !== 'all_employees') {
            $query->where('user_id', $employeeId);
        }

        // Get all results
        $leaves = $query->orderBy('created_at', 'desc')->get();

        // Transform for export
        $exportData = $leaves->map(function ($leave) {
            return [
                'Employee ID' => $leave->user->employee_id ?? '-',
                'Employee Name' => $leave->user->name ?? '-',
                'Department' => $leave->user->department?->name ?? '-',
                'Sub-Department' => $leave->user->subDepartment?->name ?? '-',
                'Designation' => $leave->user->designation ?? '-',
                'Leave Type' => $leave->leaveType->name ?? '-',
                'Leave Type Code' => $leave->leaveType->code ?? '-',
                'Application Type' => ucfirst($leave->type),
                'Start Date' => $leave->dates->min('date')?->format('Y-m-d') ?? '-',
                'End Date' => $leave->dates->max('date')?->format('Y-m-d') ?? '-',
                'Days' => $leave->dates->count(),
                'Reason' => $leave->reason ?? '-',
                'Cover Person' => $leave->coverPerson?->name ?? '-',
                'Status' => ucfirst($leave->status),
                'Applied On' => $leave->created_at->format('Y-m-d H:i'),
            ];
        });

        $filename = 'leave_records_' . Carbon::now()->format('Y-m-d') . '.' . $type;
        $format = $type === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LeaveRecordsExport($exportData),
            $filename,
            $format
        );
    }
}
