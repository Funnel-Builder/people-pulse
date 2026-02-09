<?php

use App\Models\User;
use App\Models\EmployeeMilestone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = User::whereNotNull('joining_date')->get();

        foreach ($users as $user) {
            // Check if joined milestone already exists to avoid duplicates
            $exists = EmployeeMilestone::where('user_id', $user->id)
                ->where('type', 'joined')
                ->exists();

            if (!$exists) {
                EmployeeMilestone::create([
                    'user_id' => $user->id,
                    'type' => 'joined',
                    'title' => 'Joined the Company',
                    'date' => $user->joining_date,
                    'department_id' => $user->department_id,
                    'sub_department_name' => $user->subDepartment?->name,
                    'description' => 'Joined as ' . $user->designation,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We rarely want to delete data in down() for data migrations, 
        // but strictly speaking, we could remove milestones created here.
        // For safety, we'll leave them or just do nothing.
    }
};
