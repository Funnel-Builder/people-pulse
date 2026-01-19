<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserLeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveSettingController extends Controller
{
    /**
     * Display leave settings page with employee list.
     */
    public function index(Request $request): Response
    {
        // Authorization: Only admins can access leave settings
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized access.');

        $employees = User::select(['id', 'name', 'email', 'employee_id', 'department_id', 'designation'])
            ->with('department:id,name')
            ->orderBy('joining_date', 'asc')
            ->orderBy('employee_id', 'asc')
            ->get();

        $leaveTypes = LeaveType::active()->get();

        return Inertia::render('settings/Leaves', [
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Get leave balances for a specific employee.
     */
    public function getEmployeeBalances(Request $request, User $user): JsonResponse
    {
        // Authorization: Only admins can view leave balances
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized access.');

        $leaveTypes = LeaveType::active()->get();

        $balances = $leaveTypes->map(function ($leaveType) use ($user) {
            $balance = UserLeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $leaveType->id)
                ->first();

            return [
                'leave_type_id' => $leaveType->id,
                'leave_type_name' => $leaveType->name,
                'leave_type_code' => $leaveType->code,
                'balance' => $balance?->balance ?? 0,
                'used' => $balance?->used ?? 0,
                'available' => $balance?->available ?? 0,
                'accrual_type' => $balance?->accrual_type ?? 'manual',
                'attendance_days_threshold' => $balance?->attendance_days_threshold ?? 30,
            ];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_id' => $user->employee_id,
            ],
            'balances' => $balances,
        ]);
    }

    /**
     * Update leave balances for a specific employee.
     */
    public function updateLeaveBalance(Request $request, User $user): RedirectResponse
    {
        // Authorization: Only admins can update leave balances
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized access.');

        $validated = $request->validate([
            'balances' => 'required|array',
            'balances.*.leave_type_id' => 'required|exists:leave_types,id',
            'balances.*.balance' => 'required|numeric|min:0|max:365',
            'balances.*.accrual_type' => 'required|in:manual,attendance',
            'balances.*.attendance_days_threshold' => 'nullable|integer|min:1|max:365',
        ]);

        foreach ($validated['balances'] as $balanceData) {
            $isAttendanceBased = $balanceData['accrual_type'] === 'attendance';

            UserLeaveBalance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $balanceData['leave_type_id'],
                ],
                [
                    // For attendance-based: balance is 0 (will be calculated by scheduler)
                    // For manual: use the provided balance value
                    'balance' => $isAttendanceBased ? 0 : $balanceData['balance'],
                    'accrual_type' => $balanceData['accrual_type'],
                    'attendance_days_threshold' => $isAttendanceBased
                        ? ($balanceData['attendance_days_threshold'] ?? $balanceData['balance'] ?? 30)
                        : null,
                ]
            );
        }

        // Trigger immediate accrual calculation for this user's attendance-based leaves
        \Illuminate\Support\Facades\Artisan::call('leave:calculate-accrual', [
            '--user' => $user->id,
        ]);

        return redirect()->back()->with('success', "Leave balances updated for {$user->name}.");
    }
}
