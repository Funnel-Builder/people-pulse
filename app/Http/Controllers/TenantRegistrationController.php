<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class TenantRegistrationController extends Controller
{
    /**
     * Show the tenant registration form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/TenantRegister');
    }

    /**
     * Handle the tenant registration.
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'office_id' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                'unique:tenants,id',
            ],
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $officeId = Str::lower($request->office_id);

        // Create tenant (this automatically creates the database!)
        $tenant = Tenant::create([
            'id' => $officeId,
            'name' => $request->company_name,
            'email' => $request->admin_email,
            'status' => 'active',
        ]);

        // Create admin user in tenant's database
        $tenant->run(function () use ($request) {
            // Create default leave types
            $this->seedDefaultData();

            // Create admin user
            User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role' => 'admin',
                'designation' => 'Administrator',
                'employee_id' => 'ADMIN-001',
            ]);
        });

        return redirect("/app/{$tenant->id}/login")
            ->with('success', 'Office registered successfully! Please login with your credentials.');
    }

    /**
     * Seed default data for new tenant.
     */
    protected function seedDefaultData(): void
    {
        // Create default leave types
        $leaveTypes = [
            ['name' => 'Annual Leave', 'code' => 'annual-leave', 'is_active' => true],
            ['name' => 'Sick Leave', 'code' => 'sick-leave', 'is_active' => true],
            ['name' => 'Casual Leave', 'code' => 'casual-leave', 'is_active' => true],
            ['name' => 'Maternity Leave', 'code' => 'maternity-leave', 'is_active' => true],
            ['name' => 'Paternity Leave', 'code' => 'paternity-leave', 'is_active' => true],
        ];

        foreach ($leaveTypes as $type) {
            \App\Models\LeaveType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        // Create default settings
        \App\Models\Setting::updateOrCreate(
            ['key' => 'office_start_time'],
            ['value' => '09:00']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'office_end_time'],
            ['value' => '18:00']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'late_threshold_minutes'],
            ['value' => '15']
        );
    }
}
