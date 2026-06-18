<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\ProcessAttendanceAdjustmentRequest;
use App\Models\AttendanceAdjustmentRequest;
use App\Services\AttendanceAdjustmentService;
use Illuminate\Http\RedirectResponse;

class AttendanceAdjustmentApprovalController extends Controller
{
    public function __construct(
        protected AttendanceAdjustmentService $service
    ) {
    }

    /**
     * Approve or reject the current approval step.
     */
    public function process(
        ProcessAttendanceAdjustmentRequest $request,
        AttendanceAdjustmentRequest $adjustment
    ): RedirectResponse {
        $this->authorize('process', $adjustment);

        $validated = $request->validated();

        try {
            $this->service->processApproval(
                $adjustment,
                $request->user(),
                $validated['action'],
                $validated['comment'] ?? null
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $validated['action'] === 'approve'
            ? 'Request approved.'
            : 'Request rejected.';

        return back()->with('success', $message);
    }
}
