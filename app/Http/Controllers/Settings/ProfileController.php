<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load(['department', 'subDepartment']);

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'userData' => [
                'id' => $user->id,
                'employee_id' => $user->employee_id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_picture' => $user->profile_picture,
                'department' => $user->department?->name,
                'sub_department' => $user->subDepartment?->name,
                'designation' => $user->designation,
                'role' => $user->role,
                'joining_date' => $user->joining_date?->format('Y-m-d'),
                // Editable additional info
                'nid_number' => $user->nid_number,
                'nationality' => $user->nationality,
                'fathers_name' => $user->fathers_name,
                'mothers_name' => $user->mothers_name,
                'graduated_institution' => $user->graduated_institution,
                'permanent_address' => $user->permanent_address,
                'present_address' => $user->present_address,
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Update basic info (name only, email is readonly)
        $user->fill($request->safe()->only(['name']));

        // Update additional info fields
        $user->fill($request->safe()->only([
            'nid_number',
            'nationality',
            'fathers_name',
            'mothers_name',
            'graduated_institution',
            'permanent_address',
            'present_address',
        ]));

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $user->profile_picture = $path;
        }

        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Remove the user's profile picture.
     */
    public function removeProfilePicture(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
            $user->profile_picture = null;
            $user->save();
        }

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Delete profile picture if exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
