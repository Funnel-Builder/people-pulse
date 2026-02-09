<?php

namespace App\Http\Controllers;

use App\Models\EmployeeMilestone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EmployeeMilestoneController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, User $employee)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:joined,promoted,transfer,award,other',
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'sub_department_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $employee->milestones()->create($validated);

        return Redirect::back()->with('success', 'Milestone added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeMilestone $milestone)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:joined,promoted,transfer,award,other',
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
            'sub_department_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $milestone->update($validated);

        return Redirect::back()->with('success', 'Milestone updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeMilestone $milestone)
    {
        $milestone->delete();

        return Redirect::back()->with('success', 'Milestone removed successfully.');
    }
}
