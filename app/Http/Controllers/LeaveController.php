<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leave\StoreAdvanceLeaveRequest;
use App\Http\Requests\Leave\StorePostLeaveRequest;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\UserLeaveBalance;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {
    }

    /**
     * Display My Leaves page (calendar view).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $leaves = $this->leaveService->getUserLeaves($user);

        // Transform leaves for calendar display
        $calendarLeaves = $leaves->flatMap(function ($leave) {
            return $leave->dates->map(function ($leaveDate) use ($leave) {
                return [
                    'id' => $leave->id,
                    'date' => $leaveDate->date->format('Y-m-d'),
                    'status' => $leave->status,
                    'type' => $leave->type,
                    'leave_type' => $leave->leaveType->name,
                    'reason' => $leave->reason,
                ];
            });
        });

        // Get leave balances for the user
        $leaveTypes = LeaveType::active()->get();
        $leaveBalances = $leaveTypes->map(function ($leaveType) use ($user) {
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
            ];
        });

        // Get next 5 upcoming holidays
        $upcomingHolidays = \App\Models\Holiday::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->take(5)
            ->get();

        return Inertia::render('leaves/Index', [
            'leaves' => $leaves,
            'calendarLeaves' => $calendarLeaves,
            'leaveBalances' => $leaveBalances,
            'upcomingHolidays' => $upcomingHolidays,
        ]);
    }

    /**
     * Display Leave Application page.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $coverPersonOptions = $this->leaveService->getCoverPersonOptions($user);
        $leaveTypes = LeaveType::active()->get();

        // Get leave balances for the user
        $leaveBalances = $leaveTypes->map(function ($leaveType) use ($user) {
            $balance = UserLeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $leaveType->id)
                ->first();

            return [
                'leave_type_code' => $leaveType->code,
                'leave_type_name' => $leaveType->name,
                'balance' => $balance?->balance ?? 0,
                'used' => $balance?->used ?? 0,
                'available' => $balance?->available ?? 0,
            ];
        });

        return Inertia::render('leaves/Application', [
            'coverPersonOptions' => $coverPersonOptions,
            'leaveTypes' => $leaveTypes,
            'leaveBalances' => $leaveBalances,
            'warningDays' => config('leave.warning_days', 2),
            'defaultAdvanceLeaveType' => config('leave.default_advance_leave_type', 'casual'),
            'defaultPostLeaveType' => config('leave.default_post_leave_type', 'sick'),
        ]);
    }

    /**
     * Store an advance leave application.
     */
    public function storeAdvance(StoreAdvanceLeaveRequest $request)
    {
        $leave = $this->leaveService->createAdvanceLeave(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('leaves.index')
            ->with('success', 'Advance leave application submitted successfully.');
    }

    /**
     * Store a post leave application.
     */
    public function storePost(StorePostLeaveRequest $request)
    {
        $leave = $this->leaveService->createPostLeave(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('leaves.index')
            ->with('success', 'Post leave application submitted successfully.');
    }

    /**
     * Display Cover Requests page (for cover persons).
     */
    public function requests(Request $request): Response
    {
        $user = $request->user();
        $pendingLeaves = $this->leaveService->getCoverRequestsForUser($user);
        $historyLeaves = $this->leaveService->getCoverRequestHistory($user);
        $stats = $this->leaveService->getCoverRequestStats($user, $historyLeaves);

        return Inertia::render('leaves/Requests', [
            'pendingLeaves' => $pendingLeaves,
            'historyLeaves' => $historyLeaves,
            'stats' => $stats,
            'pageTitle' => 'Cover Requests',
            'pageDescription' => 'Leave applications where you are the designated cover person',
        ]);
    }

    /**
     * Display Leave Approvals page (for managers and admins).
     */
    public function approvals(Request $request): Response
    {
        $user = $request->user();

        // Only managers and admins can access this page
        if (!$user->isManager() && !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $pendingLeaves = $this->leaveService->getManagerAdminApprovals($user);
        $historyLeaves = $this->leaveService->getApprovalHistory($user);
        $stats = $this->leaveService->getApprovalStats($user, $historyLeaves);

        return Inertia::render('leaves/Requests', [
            'pendingLeaves' => $pendingLeaves,
            'historyLeaves' => $historyLeaves,
            'stats' => $stats,
            'pageTitle' => 'Leave Approvals',
            'pageDescription' => 'Leave applications pending your approval',
        ]);
    }

    /**
     * Show a specific leave application.
     */
    public function show(Request $request, Leave $leave): Response
    {
        $user = $request->user();

        // Allow viewing if user is:
        // 1. The leave owner
        // 2. The designated cover person
        // 3. A manager or admin
        $canView = $leave->user_id === $user->id
            || $leave->cover_person_id === $user->id
            || $user->isManager()
            || $user->isAdmin();

        if (!$canView) {
            abort(403, 'Unauthorized');
        }

        $leave->load(['dates', 'leaveType', 'user', 'coverPerson', 'approvals.approver']);

        return Inertia::render('leaves/Show', [
            'leave' => $leave,
        ]);
    }

    /**
     * Cancel a leave application.
     */
    public function cancel(Request $request, Leave $leave)
    {
        // Only allow cancelling own pending leaves
        if ($leave->user_id !== $request->user()->id || $leave->status !== Leave::STATUS_PENDING) {
            abort(403, 'Unauthorized');
        }

        $leave->update(['status' => Leave::STATUS_CANCELLED]);

        return redirect()->route('leaves.index')
            ->with('success', 'Leave application cancelled successfully.');
    }
}
