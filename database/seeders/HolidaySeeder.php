<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            // February
            [
                'name' => 'International Mother Language Day',
                'date' => '2026-02-21',
                'type' => 'national',
                'is_recurring' => true,
            ],
            [
                'name' => 'Shab-E-Barat',
                'date' => '2026-02-04',
                'type' => 'religious',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
            ],
            [
                'name' => 'Shab-E-Kadr',
                'date' => '2026-02-17',
                'type' => 'religious',
                'is_recurring' => false,
            ],
            [
                'name' => 'Jumatul Bida',
                'date' => '2026-02-20',
                'type' => 'religious',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Fitr',
                'date' => '2026-02-21',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Fitr',
                'date' => '2026-02-22',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Fitr',
                'date' => '2026-02-23',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Independence Day',
                'date' => '2026-02-26',
                'type' => 'national',
                'is_recurring' => true,
            ],
            // March (User said "01 Mar - May Day", assuming May 1st)
            [
                'name' => 'May Day',
                'date' => '2026-05-01',
                'type' => 'national',
                'is_recurring' => true,
            ],
            // April
            [
                'name' => 'Pohela Boishakh',
                'date' => '2026-04-14',
                'type' => 'national',
                'is_recurring' => true,
            ],
            // May
            [
                'name' => 'National Mass Uprising Day',
                'date' => '2026-05-05',
                'type' => 'national',
                'is_recurring' => false, // Not sure if recurring
            ],
            [
                'name' => 'Eid-E-Miladun Nabi',
                'date' => '2026-05-26',
                'type' => 'religious',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
            ],
            // June
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-06-26',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-06-27',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-06-28',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-06-29',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-06-30',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha', // Corrected from 31 Jun
                'date' => '2026-07-01',
                'type' => 'national',
                'is_recurring' => false,
            ],
            // October
            [
                'name' => 'Durga Puja',
                'date' => '2026-10-21',
                'type' => 'religious',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
            ],
            // December
            [
                'name' => 'Victory Day',
                'date' => '2026-12-16',
                'type' => 'national',
                'is_recurring' => true,
            ],
        ];

        foreach ($holidays as $holiday) {
            \App\Models\Holiday::create($holiday);
        }
    }
}
