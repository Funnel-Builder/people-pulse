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
                'name' => 'Shab-E-Barat',
                'date' => '2026-02-04',
                'type' => 'religious',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
            ],
            [
                'name' => 'International Mother Language Day',
                'date' => '2026-02-21',
                'type' => 'national',
                'is_recurring' => true,
            ],

            // March
            [
                'name' => 'Shab-E-Kadr',
                'date' => '2026-03-17',
                'type' => 'religious',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
            ],
            [
                'name' => 'Jumatul Bida',
                'date' => '2026-03-20',
                'type' => 'religious',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Fitr',
                'date' => '2026-03-21',
                'type' => 'national',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
            ],
            [
                'name' => 'Eid-Ul-Fitr',
                'date' => '2026-03-22',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Fitr',
                'date' => '2026-03-23',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Independence Day',
                'date' => '2026-03-26',
                'type' => 'national',
                'is_recurring' => true,
            ],

            // April
            [
                'name' => 'Pohela Boishak',
                'date' => '2026-04-14',
                'type' => 'national',
                'is_recurring' => true,
            ],

            // May
            [
                'name' => 'May Day',
                'date' => '2026-05-01',
                'type' => 'national',
                'is_recurring' => true,
            ],

            // June (Eid-Ul-Azha holidays)
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-05-26',
                'type' => 'national',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-05-27',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-05-28',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-05-29',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-05-30',
                'type' => 'national',
                'is_recurring' => false,
            ],
            [
                'name' => 'Eid-Ul-Azha',
                'date' => '2026-05-31',
                'type' => 'national',
                'is_recurring' => false,
            ],

            // August
            [
                'name' => 'National Mass Uprising Day',
                'date' => '2026-08-05',
                'type' => 'national',
                'is_recurring' => true,
            ],
            [
                'name' => 'Eid-E-Miladun Nabi',
                'date' => '2026-08-26',
                'type' => 'religious',
                'is_recurring' => false,
                'description' => 'Depends on moon sighting',
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
