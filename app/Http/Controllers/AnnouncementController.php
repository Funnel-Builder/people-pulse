<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    /**
     * Check if user has permission to manage announcements.
     */
    private function checkPermission(Request $request): void
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Display a listing of announcements.
     */
    public function index(Request $request): Response
    {
        $this->checkPermission($request);

        $query = Announcement::with('createdBy:id,name')
            ->orderBy('created_at', 'desc');

        // Filter by status
        $status = $request->input('status');
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $announcements = $query->paginate(15);

        return Inertia::render('announcements/Index', [
            'announcements' => $announcements,
            'filters' => [
                'status' => $status ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create(Request $request): Response
    {
        $this->checkPermission($request);

        $departments = Department::active()->get(['id', 'name']);
        $subDepartments = SubDepartment::active()
            ->with('department:id,name')
            ->get(['id', 'name', 'department_id']);

        return Inertia::render('announcements/Create', [
            'departments' => $departments,
            'subDepartments' => $subDepartments,
            'roles' => ['admin', 'manager', 'user'],
            'types' => ['info', 'warning', 'success', 'event'],
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,event',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'in:admin,manager,user',
            'target_departments' => 'nullable|array',
            'target_departments.*' => 'exists:departments,id',
            'target_sub_departments' => 'nullable|array',
            'target_sub_departments.*' => 'exists:sub_departments,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        // Build target audience JSON
        $targetAudience = [];
        if (!empty($validated['target_roles'])) {
            $targetAudience['roles'] = $validated['target_roles'];
        }
        if (!empty($validated['target_departments'])) {
            $targetAudience['department_ids'] = array_map('intval', $validated['target_departments']);
        }
        if (!empty($validated['target_sub_departments'])) {
            $targetAudience['sub_department_ids'] = array_map('intval', $validated['target_sub_departments']);
        }

        Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_audience' => !empty($targetAudience) ? $targetAudience : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit(Request $request, Announcement $announcement): Response
    {
        $this->checkPermission($request);

        $departments = Department::active()->get(['id', 'name']);
        $subDepartments = SubDepartment::active()
            ->with('department:id,name')
            ->get(['id', 'name', 'department_id']);

        return Inertia::render('announcements/Edit', [
            'announcement' => $announcement->load('createdBy:id,name'),
            'departments' => $departments,
            'subDepartments' => $subDepartments,
            'roles' => ['admin', 'manager', 'user'],
            'types' => ['info', 'warning', 'success', 'event'],
        ]);
    }

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->checkPermission($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,event',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'in:admin,manager,user',
            'target_departments' => 'nullable|array',
            'target_departments.*' => 'exists:departments,id',
            'target_sub_departments' => 'nullable|array',
            'target_sub_departments.*' => 'exists:sub_departments,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        // Build target audience JSON
        $targetAudience = [];
        if (!empty($validated['target_roles'])) {
            $targetAudience['roles'] = $validated['target_roles'];
        }
        if (!empty($validated['target_departments'])) {
            $targetAudience['department_ids'] = array_map('intval', $validated['target_departments']);
        }
        if (!empty($validated['target_sub_departments'])) {
            $targetAudience['sub_department_ids'] = array_map('intval', $validated['target_sub_departments']);
        }

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_audience' => !empty($targetAudience) ? $targetAudience : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->checkPermission($request);

        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    /**
     * Toggle the active status of an announcement.
     */
    public function toggle(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->checkPermission($request);

        $announcement->update([
            'is_active' => !$announcement->is_active,
        ]);

        $status = $announcement->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Announcement {$status} successfully.");
    }
}
