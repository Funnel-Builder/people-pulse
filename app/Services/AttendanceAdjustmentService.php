<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceAdjustmentApproval;
use App\Models\AttendanceAdjustmentRequest;
use App\Models\AttendanceAuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AttendanceAdjustmentProcessed;
use App\Notifications\NewAttendanceAdjustmentRequest;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class AttendanceAdjustmentService
{
    /**
     * Create a late-entry / early-out adjustment request and start the approval chain.
     */
    public function create(User $user, array $data, ?UploadedFile $attachment = null): AttendanceAdjustmentRequest
    {
        $request = DB::transaction(function () use ($user, $data, $attachment) {
            // Link the day's attendance record if one already exists.
            $attendance = Attendance::forUser($user->id)
                ->forDate($data['date'])
                ->first();

            $attachmentPath = null;
            $attachmentName = null;
            if ($attachment) {
                $attachmentName = $attachment->getClientOriginalName();
                // Private disk — served only through the gated download route.
                $attachmentPath = $attachment->store('attendance-adjustments');
            }

            $request = AttendanceAdjustmentRequest::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance?->id,
                'type' => $data['type'],
                'date' => $data['date'],
                'requested_time' => $data['requested_time'],
                'reason' => $data['reason'],
                'cover_person_id' => $data['cover_person_id'],
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'status' => AttendanceAdjustmentRequest::STATUS_PENDING,
                'current_approval_step' => 1,
            ]);

            $this->createApprovalChain($request);

            return $request->load(['approvals', 'coverPerson', 'user']);
        });

        $this->notifyApprovers($request);

        return $request;
    }

    /**
     * Build the approval chain from config (cover_person -> manager -> admin).
     */
    protected function createApprovalChain(AttendanceAdjustmentRequest $request): void
    {
        $steps = config('attendance.adjustment_approval_steps', []);

        foreach ($steps as $step => $approverType) {
            AttendanceAdjustmentApproval::create([
                'adjustment_request_id' => $request->id,
                'step' => $step,
                'approver_type' => $approverType,
                'approver_id' => null,
                'status' => AttendanceAdjustmentApproval::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Process an approve/reject action for the current step.
     */
    public function processApproval(
        AttendanceAdjustmentRequest $request,
        User $approver,
        string $action,
        ?string $comment = null
    ): AttendanceAdjustmentRequest {
        return DB::transaction(function () use ($request, $approver, $action, $comment) {
            $currentApproval = $request->approvals()
                ->where('step', $request->current_approval_step)
                ->first();

            if (!$currentApproval) {
                throw new \Exception('No pending approval found.');
            }

            $currentApproval->update([
                'approver_id' => $approver->id,
                'status' => $action === 'approve'
                    ? AttendanceAdjustmentApproval::STATUS_APPROVED
                    : AttendanceAdjustmentApproval::STATUS_REJECTED,
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            if ($action === 'reject') {
                $request->update(['status' => AttendanceAdjustmentRequest::STATUS_REJECTED]);

                // Let the requester know their request was rejected.
                $this->notifyRequester($request, 'rejected', $approver, $comment);

                return $request->fresh(['approvals', 'coverPerson', 'user']);
            }

            $totalSteps = $request->getTotalSteps();

            if ($request->current_approval_step >= $totalSteps) {
                // Final approval — mark approved and excuse the attendance record.
                $request->update(['status' => AttendanceAdjustmentRequest::STATUS_APPROVED]);
                $this->excuseAttendance($request, $approver);

                // Let the requester know their request was approved.
                $this->notifyRequester($request, 'approved', $approver);
            } else {
                $request->update(['current_approval_step' => $request->current_approval_step + 1]);
                // Notify the next step's approver(s).
                $this->notifyApprovers($request->fresh(['approvals', 'coverPerson', 'user']));
                // Let the requester know an intermediate approver accepted (bell + push only).
                $this->notifyRequester($request, 'step_approved', $approver, null, $currentApproval->approver_type);
            }

            return $request->fresh(['approvals', 'coverPerson', 'user']);
        });
    }

    /**
     * Apply the approved adjustment to the day's attendance record.
     *
     * late_entry  → create/set clock_in at requested_time (or clear the late flag if already clocked in)
     * early_out   → create/set clock_out at requested_time (or clear early_exit_minutes if already clocked out)
     */
    protected function excuseAttendance(AttendanceAdjustmentRequest $request, User $approver): void
    {
        $attendance = $request->attendance
            ?? Attendance::forUser($request->user_id)->forDate($request->date)->first();

        $appTimezone = config('app.timezone');
        $requestedDateTime = Carbon::parse(
            $request->date->format('Y-m-d') . ' ' . $request->requested_time,
            $appTimezone
        );
        $reason = "Adjustment #{$request->id} ({$request->typeLabel()}) approved";

        if ($request->isLateEntry()) {
            if (!$attendance || !$attendance->hasClockedIn()) {
                // No attendance or clock_in missing — create the record with the approved time.
                // is_late is false because the lateness is excused by this approval.
                $breakMinutes = Setting::get('attendance.default_break_minutes', 60);

                $attendance = Attendance::updateOrCreate(
                    ['user_id' => $request->user_id, 'date' => $request->date],
                    [
                        'clock_in'              => $requestedDateTime,
                        'is_late'               => false,
                        'late_minutes'          => 0,
                        'status'                => 'present',
                        'break_minutes'         => $breakMinutes,
                        'clock_in_ip'           => request()->ip(),
                        'clock_in_user_agent'   => 'system:adjustment_approved',
                    ]
                );

                $request->update(['attendance_id' => $attendance->id]);

                AttendanceAuditLog::create([
                    'attendance_id' => $attendance->id,
                    'changed_by'    => $approver->id,
                    'field_changed' => 'clock_in',
                    'old_value'     => null,
                    'new_value'     => $requestedDateTime->toDateTimeString(),
                    'reason'        => $reason,
                    'ip_address'    => request()->ip(),
                ]);

                return;
            }

            // Already clocked in — just excuse the late flag.
            $changes = ['is_late' => false, 'late_minutes' => 0];

        } else {
            // early_out
            if (!$attendance || !$attendance->hasClockedIn()) {
                Log::info('[Attendance Adjustment] No clock-in found; cannot apply early-out adjustment', [
                    'adjustment_id' => $request->id,
                    'user_id'       => $request->user_id,
                    'date'          => $request->date,
                ]);
                return;
            }

            if (!$attendance->hasClockedOut()) {
                // Set clock_out at the approved time and recalculate hours.
                $grossMinutes = (int) max(0, $attendance->clock_in->diffInMinutes($requestedDateTime, false));
                $breakMinutes = $attendance->break_minutes ?? Setting::get('attendance.default_break_minutes', 60);
                $netMinutes   = (int) max(0, $grossMinutes - $breakMinutes);

                $attendance->update([
                    'clock_out'           => $requestedDateTime,
                    'gross_minutes'       => $grossMinutes,
                    'net_minutes'         => $netMinutes,
                    'early_exit_minutes'  => 0,
                    'clock_out_ip'        => request()->ip(),
                    'clock_out_user_agent' => 'system:adjustment_approved',
                ]);

                AttendanceAuditLog::create([
                    'attendance_id' => $attendance->id,
                    'changed_by'    => $approver->id,
                    'field_changed' => 'clock_out',
                    'old_value'     => null,
                    'new_value'     => $requestedDateTime->toDateTimeString(),
                    'reason'        => $reason,
                    'ip_address'    => request()->ip(),
                ]);

                return;
            }

            // Already clocked out — just excuse the early_exit_minutes penalty.
            $changes = ['early_exit_minutes' => 0];
        }

        // Apply flag-clearing changes with audit log.
        foreach ($changes as $field => $newValue) {
            $oldValue = $attendance->{$field};
            if ($oldValue == $newValue) {
                continue;
            }

            AttendanceAuditLog::create([
                'attendance_id' => $attendance->id,
                'changed_by'    => $approver->id,
                'field_changed' => $field,
                'old_value'     => $oldValue,
                'new_value'     => $newValue,
                'reason'        => $reason,
                'ip_address'    => request()->ip(),
            ]);
        }

        $attendance->update($changes);
    }


    /**
     * Owner cancels a still-pending request.
     */
    public function cancel(AttendanceAdjustmentRequest $request, User $user): AttendanceAdjustmentRequest
    {
        if ($request->user_id !== $user->id) {
            throw new \Exception('You can only cancel your own request.');
        }

        if (!$request->isPending()) {
            throw new \Exception('Only pending requests can be cancelled.');
        }

        $request->update(['status' => AttendanceAdjustmentRequest::STATUS_CANCELLED]);

        return $request->fresh();
    }

    /**
     * Notify the approver(s) responsible for the request's current step.
     */
    protected function notifyApprovers(AttendanceAdjustmentRequest $request): void
    {
        $recipients = $this->resolveStepApprovers($request, $request->current_approval_step);

        if ($recipients->isEmpty()) {
            Log::info('[Attendance Adjustment] No approver resolved; skipping notification', [
                'adjustment_id' => $request->id,
                'step' => $request->current_approval_step,
            ]);
            return;
        }

        $request->loadMissing(['user', 'coverPerson']);
        $notification = new NewAttendanceAdjustmentRequest($request);

        // Deliver each channel independently so a failing email cannot swallow
        // the in-app bell or browser push (mirrors LeaveService).
        $channels = [
            'database' => 'database',
            'web push' => WebPushChannel::class,
            'email' => 'mail',
        ];

        foreach ($channels as $label => $channel) {
            try {
                Notification::sendNow($recipients, $notification, [$channel]);
            } catch (\Throwable $e) {
                Log::error("[Attendance Adjustment] {$label} channel failed", [
                    'adjustment_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify the employee who submitted the request of the final outcome
     * (approved / rejected) across bell, push and email.
     */
    protected function notifyRequester(
        AttendanceAdjustmentRequest $request,
        string $outcome,
        User $approver,
        ?string $comment = null,
        ?string $byRole = null
    ): void {
        $request->loadMissing('user');
        $recipient = $request->user;

        if (!$recipient) {
            return;
        }

        $notification = new AttendanceAdjustmentProcessed($request, $outcome, $approver, $comment, $byRole);

        // Intermediate (cover/manager) approvals: bell + push only.
        // Final approval and rejection also send email.
        $channels = $outcome === 'step_approved'
            ? ['database' => 'database', 'web push' => WebPushChannel::class]
            : ['database' => 'database', 'web push' => WebPushChannel::class, 'email' => 'mail'];

        foreach ($channels as $label => $channel) {
            try {
                Notification::sendNow($recipient, $notification, [$channel]);
            } catch (\Throwable $e) {
                Log::error("[Attendance Adjustment] requester {$label} channel failed", [
                    'adjustment_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Resolve the approver user(s) for a given step.
     */
    protected function resolveStepApprovers(AttendanceAdjustmentRequest $request, int $step): Collection
    {
        $type = config("attendance.adjustment_approval_steps.{$step}");

        if ($type === AttendanceAdjustmentApproval::TYPE_COVER_PERSON) {
            return $request->cover_person_id
                ? User::where('id', $request->cover_person_id)->get()
                : collect([]);
        }

        if ($type === AttendanceAdjustmentApproval::TYPE_MANAGER) {
            return $this->managersForEmployee($request->user);
        }

        if ($type === AttendanceAdjustmentApproval::TYPE_ADMIN) {
            return User::where('role', 'admin')->get();
        }

        return collect([]);
    }

    /**
     * Find the manager(s) responsible for an employee. Mirrors LeaveService:
     * a manager in the same department who either manages the employee's
     * sub-department or manages the whole department.
     */
    protected function managersForEmployee(User $employee): Collection
    {
        if (!$employee->department_id) {
            return collect([]);
        }

        return User::where('role', 'manager')
            ->where('department_id', $employee->department_id)
            ->where('id', '!=', $employee->id)
            ->get()
            ->filter(function (User $manager) use ($employee) {
                $managedSubDeptIds = $manager->getManagedSubDepartmentIds();

                return empty($managedSubDeptIds)
                    || in_array($employee->sub_department_id, $managedSubDeptIds, true);
            })
            ->values();
    }

    /**
     * Cover-person/manager/admin scoped list of people the user can pick as cover.
     * Reuses the same scoping rules as leave cover persons.
     */
    public function coverPersonOptions(User $user): Collection
    {
        if ($user->isAdmin()) {
            return User::where('id', '!=', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id', 'designation']);
        }

        if ($user->isManager()) {
            $managedSubDeptIds = $user->getManagedSubDepartmentIds();
            if (!empty($managedSubDeptIds)) {
                return User::whereIn('sub_department_id', $managedSubDeptIds)
                    ->where('id', '!=', $user->id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'employee_id', 'designation']);
            }
        }

        if (!$user->sub_department_id) {
            return collect([]);
        }

        return User::where('sub_department_id', $user->sub_department_id)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id', 'designation']);
    }

    /**
     * Determine whether a user may act on the request's current approval step.
     */
    public function canApprove(User $user, AttendanceAdjustmentRequest $request): bool
    {
        if (!$request->isPending()) {
            return false;
        }

        $currentApproval = $request->currentApproval();
        if (!$currentApproval) {
            return false;
        }

        switch ($currentApproval->approver_type) {
            case AttendanceAdjustmentApproval::TYPE_COVER_PERSON:
                return $request->cover_person_id === $user->id || $user->isAdmin();

            case AttendanceAdjustmentApproval::TYPE_MANAGER:
                if (!$user->isManager() && !$user->isAdmin()) {
                    return false;
                }
                if ($user->isAdmin()) {
                    return true;
                }
                $managedSubDeptIds = $user->getManagedSubDepartmentIds();
                $employee = $request->user;
                return $employee->department_id === $user->department_id
                    && (empty($managedSubDeptIds) || in_array($employee->sub_department_id, $managedSubDeptIds, true));

            case AttendanceAdjustmentApproval::TYPE_ADMIN:
                return $user->isAdmin();

            default:
                return false;
        }
    }

    /**
     * Pending requests awaiting action from this user (cover, manager, or admin step).
     */
    public function getPendingApprovalsForUser(User $user): Collection
    {
        $base = fn () => AttendanceAdjustmentRequest::with(['user', 'coverPerson', 'approvals.approver', 'attendance'])
            ->where('status', AttendanceAdjustmentRequest::STATUS_PENDING);

        // Step 1: cover-person requests assigned to this user.
        $coverRequests = $base()
            ->where('cover_person_id', $user->id)
            ->where('current_approval_step', 1)
            ->get();

        $scoped = collect([]);

        if ($user->isAdmin()) {
            // Admin acts on step 3 (admin step).
            $scoped = $base()
                ->where('current_approval_step', 3)
                ->get();
        } elseif ($user->isManager()) {
            // Manager acts on step 2 for employees in their scope.
            $managedSubDeptIds = $user->getManagedSubDepartmentIds();
            $scoped = $base()
                ->where('current_approval_step', 2)
                ->whereHas('user', function ($q) use ($user, $managedSubDeptIds) {
                    $q->where('department_id', $user->department_id);
                    if (!empty($managedSubDeptIds)) {
                        $q->whereIn('sub_department_id', $managedSubDeptIds);
                    }
                })
                ->get();
        }

        return $coverRequests->merge($scoped)
            ->unique('id')
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Count of pending requests this user must act on (for the sidebar badge).
     */
    public function getPendingApprovalCount(User $user): int
    {
        return $this->getPendingApprovalsForUser($user)->count();
    }

    /**
     * All adjustment requests submitted by a user (for their own history).
     */
    public function getUserRequests(User $user): Collection
    {
        return AttendanceAdjustmentRequest::with(['coverPerson', 'approvals'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * History of adjustment requests this user has acted on (approved/rejected)
     * at any step (cover person, manager or admin). Each request is annotated
     * with the action this user took and when.
     */
    public function getApprovalHistory(User $user): Collection
    {
        return AttendanceAdjustmentRequest::with(['user', 'coverPerson', 'approvals.approver', 'attendance'])
            ->whereHas('approvals', function ($q) use ($user) {
                $q->where('approver_id', $user->id)
                    ->where('status', '!=', AttendanceAdjustmentApproval::STATUS_PENDING)
                    ->whereNotNull('acted_at');
            })
            ->get()
            ->map(function (AttendanceAdjustmentRequest $request) use ($user) {
                // The action this user personally took on this request.
                $myAction = $request->approvals
                    ->where('approver_id', $user->id)
                    ->whereNotNull('acted_at')
                    ->sortByDesc('acted_at')
                    ->first();

                $request->action_date = $myAction?->acted_at;
                $request->action_status = $myAction?->status;
                $request->action_type = $myAction?->approver_type;

                return $request;
            })
            ->sortByDesc('action_date')
            ->values();
    }

    /**
     * Stats for the adjustment history view (mirrors the cover-request stats).
     */
    public function getApprovalStats(User $user, ?Collection $history = null): array
    {
        $history = $history ?? $this->getApprovalHistory($user);

        return [
            'total_handled' => $history->count(),
            'pending' => $this->getPendingApprovalCount($user),
            'approved' => $history->where('action_status', AttendanceAdjustmentApproval::STATUS_APPROVED)->count(),
        ];
    }
}
