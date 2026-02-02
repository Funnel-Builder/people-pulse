<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('settings/Departments', [
            'departments' => Department::with('subDepartments')
                ->orderBy('name')
                ->get()
                ->map(fn($dept) => [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'description' => $dept->description,
                    'is_active' => $dept->is_active,
                    'sub_departments' => $dept->subDepartments->map(fn($sub) => [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'description' => $sub->description,
                        'is_active' => $sub->is_active,
                    ]),
                ]),
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Department::create($validated);

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $department->update($validated);

        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete department because it has assigned users.');
        }

        if ($department->subDepartments()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete department because it has sub-departments. Delete them first.');
        }

        $department->delete();

        return redirect()->back()->with('success', 'Department deleted successfully.');
    }

    /**
     * Store a newly created sub-department.
     */
    public function storeSubDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sub_departments')->where(function ($query) use ($department) {
                    return $query->where('department_id', $department->id);
                })
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $department->subDepartments()->create($validated);

        return redirect()->back()->with('success', 'Sub-department created successfully.');
    }

    /**
     * Update the specified sub-department.
     */
    public function updateSubDepartment(Request $request, SubDepartment $subDepartment)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sub_departments')->where(function ($query) use ($subDepartment) {
                    return $query->where('department_id', $subDepartment->department_id);
                })->ignore($subDepartment->id)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subDepartment->update($validated);

        return redirect()->back()->with('success', 'Sub-department updated successfully.');
    }

    /**
     * Remove the specified sub-department.
     */
    public function destroySubDepartment(SubDepartment $subDepartment)
    {
        if ($subDepartment->users()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete sub-department because it has assigned users.');
        }

        $subDepartment->delete();

        return redirect()->back()->with('success', 'Sub-department deleted successfully.');
    }
}
