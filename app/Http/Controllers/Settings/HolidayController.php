<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;


use App\Models\Holiday;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * Display a listing of the holidays.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);

        $holidays = Holiday::whereYear('date', $year)
            ->orderBy('date')
            ->get();

        return Inertia::render('settings/Holidays', [
            'holidays' => $holidays,
            'year' => (int) $year,
        ]);
    }

    /**
     * Store a newly created holiday in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:national,company,optional,religious',
            'is_recurring' => 'boolean',
            'description' => 'nullable|string',
        ]);

        Holiday::create($validated);

        return redirect()->back()->with('success', 'Holiday created successfully.');
    }

    /**
     * Update the specified holiday in storage.
     */
    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:national,company,optional,religious',
            'is_recurring' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $holiday->update($validated);

        return redirect()->back()->with('success', 'Holiday updated successfully.');
    }

    /**
     * Remove the specified holiday from storage.
     */
    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return redirect()->back()->with('success', 'Holiday deleted successfully.');
    }
}
