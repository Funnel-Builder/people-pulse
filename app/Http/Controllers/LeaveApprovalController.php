<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Models\Leave;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;

class LeaveApprovalController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {
    }

    /**
     * Process an approval or rejection action.
     */
    public function process(ApproveLeaveRequest $request, Leave $leave): RedirectResponse
    {
        // Check if user can approve this leave
        $user = $request->user();
        $currentApproval = $leave->approvals()
            ->where('step', $leave->current_approval_step)
            ->where('status', 'pending')
            ->first();

        if (!$currentApproval) {
            abort(403, 'No pending approval for this leave.');
        }

        // Check if user is authorized to approve
        $canApprove = false;

        if ($currentApproval->approver_type === 'cover_person') {
            if ($leave->cover_person_id === $user->id || $user->isAdmin()) {
                $canApprove = true;
            }
        } elseif ($currentApproval->approver_type === 'manager') {
            if ($user->isManager() || $user->isAdmin()) {
                $canApprove = true;
            }
        } elseif ($currentApproval->approver_type === 'admin' && $user->isAdmin()) {
            $canApprove = true;
        }

        if (!$canApprove) {
            abort(403, 'You are not authorized to approve this leave.');
        }

        $validated = $request->validated();

        $this->leaveService->processApproval(
            $leave,
            $user,
            $validated['action'],
            $validated['comment'] ?? null
        );

        $message = $validated['action'] === 'approve'
            ? 'Leave application approved successfully.'
            : 'Leave application rejected.';

        return redirect()->back()
            ->with('success', $message);
    }
}

