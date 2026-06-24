<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveService;

class LeavePolicy
{
    protected LeaveService $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Determine if the user can view any leaves.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their own leaves
    }

    /**
     * Determine if the user can view the leave.
     */
    public function view(User $user, Leave $leave): bool
    {
        // Owner can view their own leave
        if ($leave->user_id === $user->id) {
            return true;
        }

        // Cover person can view
        if ($leave->cover_person_id === $user->id) {
            return true;
        }

        // Admin can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Manager can view leaves from their department
        if ($user->isManager()) {
            $leaveUser = $leave->user;
            $managedSubDeptIds = $user->getManagedSubDepartmentIds();

            return $leaveUser->department_id === $user->department_id &&
                (empty($managedSubDeptIds) || in_array($leaveUser->sub_department_id, $managedSubDeptIds));
        }

        return false;
    }

    /**
     * Determine if the user can create a leave.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create leave applications
    }

    /**
     * Determine if the user can approve/reject the leave.
     */
    public function approve(User $user, Leave $leave): bool
    {
        return $this->leaveService->canApprove($user, $leave);
    }

    /**
     * Determine if the user can view the leave requests page.
     */
    public function viewRequests(User $user): bool
    {
        // Users can view if they might have pending approvals
        // This includes anyone who could be a cover person, manager, or admin
        return true;
    }

    /**
     * Determine if the user can cancel the leave.
     */
    public function cancel(User $user, Leave $leave): bool
    {
        // Only the owner can cancel, and only if still pending
        return $leave->user_id === $user->id && $leave->isPending();
    }
}
