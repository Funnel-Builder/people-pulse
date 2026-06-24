<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Casual Leave',
                'code' => 'casual',
                'description' => 'Leave for personal matters or emergencies',
                'is_active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'description' => 'Leave due to illness or medical appointments',
                'is_active' => true,
            ],
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'description' => 'Yearly entitled vacation leave',
                'is_active' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'maternity',
                'description' => 'Leave for maternity purposes',
                'is_active' => true,
            ],
            [
                'name' => 'Other Leave',
                'code' => 'other',
                'description' => 'Other types of leave not covered above',
                'is_active' => true,
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
