<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\StoreAttendanceAdjustmentRequest;
use App\Models\AttendanceAdjustmentRequest;
use App\Services\AttendanceAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceAdjustmentController extends Controller
{
    public function __construct(
        protected AttendanceAdjustmentService $service
    ) {
    }

    /**
     * Submit a late-entry / early-out adjustment request.
     */
    public function store(StoreAttendanceAdjustmentRequest $request): RedirectResponse
    {
        try {
            $this->service->create(
                $request->user(),
                $request->safe()->except('attachment'),
                $request->file('attachment')
            );

            return back()->with('success', 'Your request has been submitted for approval.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a pending request (owner only).
     */
    public function cancel(AttendanceAdjustmentRequest $adjustment): RedirectResponse
    {
        $this->authorize('cancel', $adjustment);

        try {
            $this->service->cancel($adjustment, request()->user());

            return back()->with('success', 'Request cancelled.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approver inbox — pending requests awaiting this user's action.
     */
    public function approvals(Request $request): Response
    {
        $user = $request->user();

        $pending = $this->service->getPendingApprovalsForUser($user)
            ->map(fn (AttendanceAdjustmentRequest $r) => $this->present($r));

        $history = $this->service->getApprovalHistory($user);
        $stats = $this->service->getApprovalStats($user, $history);

        // The approver's own submitted late/early requests (for "View Own History").
        $ownRequests = $this->service->getUserRequests($user)
            ->load(['user', 'approvals.approver'])
            ->map(fn (AttendanceAdjustmentRequest $r) => $this->present($r));

        return Inertia::render('attendance/Adjustments', [
            'pendingRequests' => $pending->values(),
            'historyRequests' => $history
                ->map(fn (AttendanceAdjustmentRequest $r) => $this->presentHistory($r))
                ->values(),
            'ownRequests' => $ownRequests->values(),
            'stats' => $stats,
            'coverPersonOptions' => $this->service->coverPersonOptions($user)->values(),
        ]);
    }

    /**
     * Download the supporting attachment behind an authorization check.
     */
    public function attachment(AttendanceAdjustmentRequest $adjustment): StreamedResponse
    {
        $this->authorize('downloadAttachment', $adjustment);

        abort_if(!$adjustment->attachment_path || !Storage::exists($adjustment->attachment_path), 404);

        return Storage::download(
            $adjustment->attachment_path,
            $adjustment->attachment_name ?? basename($adjustment->attachment_path)
        );
    }

    /**
     * Shape a request for the frontend, including a current-step label.
     */
    protected function present(AttendanceAdjustmentRequest $request): array
    {
        $currentApproval = $request->approvals->firstWhere('step', $request->current_approval_step);

        return [
            'id' => $request->id,
            'type' => $request->type,
            'type_label' => $request->typeLabel(),
            'date' => $request->date->toDateString(),
            'requested_time' => $request->requested_time,
            'reason' => $request->reason,
            'status' => $request->status,
            'current_approval_step' => $request->current_approval_step,
            'current_approver_type' => $currentApproval?->approver_type,
            'has_attachment' => (bool) $request->attachment_path,
            'attachment_name' => $request->attachment_name,
            'attachment_url' => $request->attachment_path
                ? route('attendance.adjustments.attachment', $request->id)
                : null,
            'user' => [
                'id' => $request->user->id,
                'name' => $request->user->name,
                'employee_id' => $request->user->employee_id,
                'designation' => $request->user->designation,
                'profile_picture' => $request->user->profile_picture,
            ],
            'cover_person' => $request->coverPerson ? [
                'id' => $request->coverPerson->id,
                'name' => $request->coverPerson->name,
            ] : null,
            'approvals' => $request->approvals
                ->sortBy('step')
                ->map(fn ($approval) => [
                    'id' => $approval->id,
                    'step' => $approval->step,
                    'approver_type' => $approval->approver_type,
                    'approver_type_label' => $this->approverTypeLabel($approval->approver_type),
                    // Falls back to the assigned cover person so their name shows
                    // even before they have acted on the request.
                    'approver_name' => $approval->approver?->name
                        ?? ($approval->approver_type === 'cover_person'
                            ? $request->coverPerson?->name
                            : null),
                    'status' => $approval->status,
                    'comment' => $approval->comment,
                    'acted_at' => $approval->acted_at?->toIso8601String(),
                ])
                ->values(),
        ];
    }

    /**
     * Human-readable label for an approver type in the approval chain.
     */
    protected function approverTypeLabel(?string $type): string
    {
        return match ($type) {
            'cover_person' => 'Cover Person',
            'manager' => 'Manager',
            'admin' => 'Admin',
            default => ucfirst(str_replace('_', ' ', (string) $type)),
        };
    }

    /**
     * Shape a request for the history view, annotated with the action this
     * user took (status, date, and the role they acted as).
     */
    protected function presentHistory(AttendanceAdjustmentRequest $request): array
    {
        return array_merge($this->present($request), [
            'action_status' => $request->action_status,
            'action_date' => $request->action_date?->toIso8601String(),
            'action_type' => $request->action_type,
        ]);
    }
}
