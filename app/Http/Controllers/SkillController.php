<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SkillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $skillGroups = SkillGroup::with('skills')->get();

        return Inertia::render('settings/Skills/Index', [
            'skillGroups' => $skillGroups,
        ]);
    }

    /**
     * Store a newly created skill group in storage.
     */
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:skill_groups,name',
        ]);

        SkillGroup::create($validated);

        return back()->with('success', 'Skill group created successfully.');
    }

    /**
     * Update the specified skill group in storage.
     */
    public function updateGroup(Request $request, SkillGroup $skillGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:skill_groups,name,' . $skillGroup->id,
        ]);

        $skillGroup->update($validated);

        return back()->with('success', 'Skill group updated successfully.');
    }

    /**
     * Remove the specified skill group from storage.
     */
    public function destroyGroup(SkillGroup $skillGroup)
    {
        if ($skillGroup->skills()->exists()) {
            return back()->with('error', 'Cannot delete skill group with associated skills. Please delete or move the skills first.');
        }

        $skillGroup->delete();

        return back()->with('success', 'Skill group deleted successfully.');
    }

    /**
     * Store a newly created skill in storage.
     */
    public function storeSkill(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'skill_group_id' => 'required|exists:skill_groups,id',
        ]);

        // Check for uniqueness within the group (optional, but good practice)
        $exists = Skill::where('name', $validated['name'])
            ->where('skill_group_id', $validated['skill_group_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'This skill already exists in the selected group.']);
        }

        Skill::create($validated);

        return back()->with('success', 'Skill created successfully.');
    }

    /**
     * Update the specified skill in storage.
     */
    public function updateSkill(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'skill_group_id' => 'required|exists:skill_groups,id',
        ]);

        // Check for uniqueness if name or group changed
        $exists = Skill::where('name', $validated['name'])
            ->where('skill_group_id', $validated['skill_group_id'])
            ->where('id', '!=', $skill->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'This skill already exists in the selected group.']);
        }

        $skill->update($validated);

        return back()->with('success', 'Skill updated successfully.');
    }

    /**
     * Remove the specified skill from storage.
     */
    public function destroySkill(Skill $skill)
    {
        $skill->users()->detach(); // Detach from all users first
        $skill->delete();

        return back()->with('success', 'Skill deleted successfully.');
    }
}
