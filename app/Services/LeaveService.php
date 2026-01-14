<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveDate;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserLeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    /**
     * Create an advance leave application.
     */
    public function createAdvanceLeave(User $user, array $data): Leave
    {
        return DB::transaction(function () use ($user, $data) {
            // Get leave type (user can select for advance leave: Casual, Annual, Maternity)
            $leaveType = LeaveType::findByCode($data['leave_type'] ?? config('leave.default_advance_leave_type', 'casual'));

            // Create the leave record
            $leave = Leave::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'type' => Leave::TYPE_ADVANCE,
                'reason' => $data['reason'],
                'cover_person_id' => $data['cover_person_id'],
                'status' => Leave::STATUS_PENDING,
                'current_approval_step' => 1,
            ]);

            // Create leave dates (multiple dates allowed for advance too)
            foreach ($data['dates'] as $date) {
                LeaveDate::create([
                    'leave_id' => $leave->id,
                    'date' => $date,
                ]);
            }

            // Create approval chain
            $this->createApprovalChain($leave);

            return $leave->load(['dates', 'approvals', 'leaveType', 'coverPerson']);
        });
    }

    /**
     * Create a post leave application.
     */
    public function createPostLeave(User $user, array $data): Leave
    {
        return DB::transaction(function () use ($user, $data) {
            // Get leave type (can be changed by user for post leave)
            $leaveType = LeaveType::findByCode($data['leave_type'] ?? config('leave.default_post_leave_type', 'sick'));

            // Create the leave record
            $leave = Leave::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'type' => Leave::TYPE_POST,
                'reason' => $data['reason'],
                'cover_person_id' => null, // No cover person for post leave
                'status' => Leave::STATUS_PENDING,
                'current_approval_step' => 1,
            ]);

            // Create leave dates (multiple dates allowed for post)
            foreach ($data['dates'] as $date) {
                LeaveDate::create([
                    'leave_id' => $leave->id,
                    'date' => $date,
                ]);
            }

            // Create approval chain
            $this->createApprovalChain($leave);

            return $leave->load(['dates', 'approvals', 'leaveType']);
        });
    }

    /**
     * Create the approval chain for a leave.
     */
    protected function createApprovalChain(Leave $leave): void
    {
        $steps = config("leave.approval_steps.{$leave->type}", []);

        foreach ($steps as $step => $approverType) {
            LeaveApproval::create([
                'leave_id' => $leave->id,
                'step' => $step,
                'approver_type' => $approverType,
                'approver_id' => null, // Will be set when someone approves
                'status' => $step === 1 ? LeaveApproval::STATUS_PENDING : LeaveApproval::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Process an approval action.
     */
    public function processApproval(Leave $leave, User $approver, string $action, ?string $comment = null): Leave
    {
        return DB::transaction(function () use ($leave, $approver, $action, $comment) {
            $currentApproval = $leave->approvals()
                ->where('step', $leave->current_approval_step)
                ->first();

            if (!$currentApproval) {
                throw new \Exception('No pending approval found.');
            }

            // Update the approval record
            $currentApproval->update([
                'approver_id' => $approver->id,
                'status' => $action === 'approve' ? LeaveApproval::STATUS_APPROVED : LeaveApproval::STATUS_REJECTED,
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            if ($action === 'reject') {
                // Reject the entire leave
                $leave->update(['status' => Leave::STATUS_REJECTED]);
            } else {
                // Check if there are more steps
                $totalSteps = $leave->getTotalSteps();

                if ($leave->current_approval_step >= $totalSteps) {
                    // All steps completed - approve the leave
                    $leave->update(['status' => Leave::STATUS_APPROVED]);

                    // Deduct leave days from user's balance
                    $leaveDays = $leave->dates()->count();
                    $balance = UserLeaveBalance::getOrCreate($leave->user_id, $leave->leave_type_id);
                    $balance->deductLeave($leaveDays);
                } else {
                    // Move to next step
                    $leave->update(['current_approval_step' => $leave->current_approval_step + 1]);
                }
            }

            return $leave->fresh(['dates', 'approvals', 'leaveType', 'coverPerson', 'user']);
        });
    }

    /**
     * Get cover person options for a user.
     * Admins can see all users, regular users see only their sub-department members.
     */
    public function getCoverPersonOptions(User $user): Collection
    {
        // Admins can select any user as cover person
        if ($user->isAdmin()) {
            return User::where('id', '!=', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id', 'designation']);
        }

        // Regular users can only select from their sub-department
        if (!$user->sub_department_id) {
            return collect([]);
        }

        return User::where('sub_department_id', $user->sub_department_id)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id', 'designation']);
    }

    /**
     * Get pending approvals for a user based on their role.
     */
    public function getPendingApprovalsForUser(User $user): Collection
    {
        $query = Leave::with(['dates', 'leaveType', 'user', 'coverPerson', 'approvals'])
            ->where('status', Leave::STATUS_PENDING);

        if ($user->isAdmin()) {
            // Admin sees all leaves at admin approval step
            $query->whereHas('approvals', function ($q) {
                $q->where('status', LeaveApproval::STATUS_PENDING)
                    ->where('approver_type', LeaveApproval::TYPE_ADMIN);
            })->where(function ($q) {
                $q->where('type', Leave::TYPE_ADVANCE)
                    ->where('current_approval_step', 3)
                    ->orWhere(function ($q2) {
                        $q2->where('type', Leave::TYPE_POST)
                            ->where('current_approval_step', 2);
                    });
            });
        } elseif ($user->isManager()) {
            // Manager sees leaves from their department at manager step
            $managedSubDeptIds = $user->getManagedSubDepartmentIds();

            $query->whereHas('user', function ($q) use ($user, $managedSubDeptIds) {
                $q->where('department_id', $user->department_id);
                if (!empty($managedSubDeptIds)) {
                    $q->whereIn('sub_department_id', $managedSubDeptIds);
                }
            })->whereHas('approvals', function ($q) {
                $q->where('status', LeaveApproval::STATUS_PENDING)
                    ->where('approver_type', LeaveApproval::TYPE_MANAGER);
            })->where(function ($q) {
                $q->where('type', Leave::TYPE_ADVANCE)
                    ->where('current_approval_step', 2)
                    ->orWhere(function ($q2) {
                        $q2->where('type', Leave::TYPE_POST)
                            ->where('current_approval_step', 1);
                    });
            });
        }

        // Cover person requests
        // Admins can see all advance leave requests at cover person step
        $coverPersonQuery = Leave::with(['dates', 'leaveType', 'user', 'coverPerson', 'approvals'])
            ->where('status', Leave::STATUS_PENDING)
            ->where('current_approval_step', 1)
            ->where('type', Leave::TYPE_ADVANCE);

        if (!$user->isAdmin()) {
            $coverPersonQuery->where('cover_person_id', $user->id);
        }

        $coverPersonLeaves = $coverPersonQuery->get();

        // Merge and return unique leaves
        if ($user->isAdmin() || $user->isManager()) {
            return $query->get()->merge($coverPersonLeaves)->unique('id');
        }

        return $coverPersonLeaves;
    }

    /**
     * Get cover requests for a user (where they are the cover person).
     * Admins can see all advance leave requests pending cover person approval.
     */
    public function getCoverRequestsForUser(User $user): Collection
    {
        $query = Leave::with(['dates', 'leaveType', 'user', 'coverPerson', 'approvals'])
            ->where('status', Leave::STATUS_PENDING)
            ->where('current_approval_step', 1)
            ->where('type', Leave::TYPE_ADVANCE);

        // Admins can see and approve all cover person requests
        if (!$user->isAdmin()) {
            $query->where('cover_person_id', $user->id);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Get pending approvals for managers and admins (excludes cover person requests).
     */
    public function getManagerAdminApprovals(User $user): Collection
    {
        if (!$user->isAdmin() && !$user->isManager()) {
            return collect([]);
        }

        $query = Leave::with(['dates', 'leaveType', 'user', 'coverPerson', 'approvals'])
            ->where('status', Leave::STATUS_PENDING);

        if ($user->isAdmin()) {
            // Admin sees all leaves at admin approval step
            $query->whereHas('approvals', function ($q) {
                $q->where('status', LeaveApproval::STATUS_PENDING)
                    ->where('approver_type', LeaveApproval::TYPE_ADMIN);
            })->where(function ($q) {
                $q->where('type', Leave::TYPE_ADVANCE)
                    ->where('current_approval_step', 3)
                    ->orWhere(function ($q2) {
                        $q2->where('type', Leave::TYPE_POST)
                            ->where('current_approval_step', 2);
                    });
            });
        } elseif ($user->isManager()) {
            // Manager sees leaves from their department at manager step
            $managedSubDeptIds = $user->getManagedSubDepartmentIds();

            $query->whereHas('user', function ($q) use ($user, $managedSubDeptIds) {
                $q->where('department_id', $user->department_id);
                if (!empty($managedSubDeptIds)) {
                    $q->whereIn('sub_department_id', $managedSubDeptIds);
                }
            })->whereHas('approvals', function ($q) {
                $q->where('status', LeaveApproval::STATUS_PENDING)
                    ->where('approver_type', LeaveApproval::TYPE_MANAGER);
            })->where(function ($q) {
                $q->where('type', Leave::TYPE_ADVANCE)
                    ->where('current_approval_step', 2)
                    ->orWhere(function ($q2) {
                        $q2->where('type', Leave::TYPE_POST)
                            ->where('current_approval_step', 1);
                    });
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Get all leaves for a user (for calendar view).
     */
    public function getUserLeaves(User $user): Collection
    {
        return Leave::with(['dates', 'leaveType', 'approvals'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Check if a date is within warning days.
     */
    public function isWarningDate(string $date): bool
    {
        $warningDays = config('leave.warning_days', 2);
        $targetDate = Carbon::parse($date);
        $warningLimit = Carbon::today()->addDays($warningDays);

        return $targetDate->lte($warningLimit) && $targetDate->gt(Carbon::today());
    }

    /**
     * Check if user can approve a leave.
     */
    public function canApprove(User $user, Leave $leave): bool
    {
        if (!$leave->isPending()) {
            return false;
        }

        $currentApproval = $leave->currentApproval();
        if (!$currentApproval) {
            return false;
        }

        switch ($currentApproval->approver_type) {
            case LeaveApproval::TYPE_COVER_PERSON:
                return $leave->cover_person_id === $user->id;

            case LeaveApproval::TYPE_MANAGER:
                if (!$user->isManager() && !$user->isAdmin()) {
                    return false;
                }
                $managedSubDeptIds = $user->getManagedSubDepartmentIds();
                $leaveUser = $leave->user;
                return $leaveUser->department_id === $user->department_id &&
                    (empty($managedSubDeptIds) || in_array($leaveUser->sub_department_id, $managedSubDeptIds));

            case LeaveApproval::TYPE_ADMIN:
                return $user->isAdmin();

            default:
                return false;
        }
    }
}
