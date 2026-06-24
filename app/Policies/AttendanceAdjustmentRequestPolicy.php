<?php

namespace App\Policies;

use App\Models\AttendanceAdjustmentRequest;
use App\Models\User;
use App\Services\AttendanceAdjustmentService;

class AttendanceAdjustmentRequestPolicy
{
    public function __construct(
        protected AttendanceAdjustmentService $service
    ) {
    }

    /**
     * Any authenticated user can submit an adjustment request for themselves.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * View a request: the owner, the current approver, or an admin.
     */
    public function view(User $user, AttendanceAdjustmentRequest $request): bool
    {
        if ($user->isAdmin() || $request->user_id === $user->id) {
            return true;
        }

        return $this->service->canApprove($user, $request)
            || $request->cover_person_id === $user->id;
    }

    /**
     * Act on the current approval step.
     */
    public function process(User $user, AttendanceAdjustmentRequest $request): bool
    {
        return $this->service->canApprove($user, $request);
    }

    /**
     * Only the owner can cancel their own pending request.
     */
    public function cancel(User $user, AttendanceAdjustmentRequest $request): bool
    {
        return $request->user_id === $user->id && $request->isPending();
    }

    /**
     * Download the supporting attachment — same audience as view.
     */
    public function downloadAttachment(User $user, AttendanceAdjustmentRequest $request): bool
    {
        return $this->view($user, $request);
    }
}
