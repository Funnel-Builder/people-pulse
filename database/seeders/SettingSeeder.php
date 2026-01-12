<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'attendance.office_start_time',
                'value' => '09:30',
                'type' => 'time',
                'description' => 'Official office start time',
            ],
            [
                'key' => 'attendance.late_grace_minutes',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Grace period in minutes before marking as late',
            ],
            [
                'key' => 'attendance.office_end_time',
                'value' => '17:30',
                'type' => 'time',
                'description' => 'Official office end time',
            ],
            [
                'key' => 'attendance.default_break_minutes',
                'value' => '60',
                'type' => 'integer',
                'description' => 'Default break duration in minutes',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
