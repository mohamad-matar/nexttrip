<?php

namespace Database\Seeders;

use App\Models\Trip;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $trips = [
            [
                'user_id' => 2, // نور كاملة
                'title' => 'رحلة دمشق التراثية',
                'budget_max' => 500,
                'trip_pace' => 'slow',
                'preferred_activity_level' => 'sensible',
                'day_count' => 3,
                'start_date' => '2024-06-01',
                'total_estimated_cost' => 150,
            ],
            [
                'user_id' => 2, // نور كاملة
                'title' => 'رحلة حلب الأثرية',
                'budget_max' => 300,
                'trip_pace' => 'medium',
                'preferred_activity_level' => 'vigour',
                'day_count' => 2,
                'start_date' => '2024-07-15',
                'total_estimated_cost' => 80,
            ],
            [
                'user_id' => 3, // محمد سليمان
                'title' => 'رحلة الساحل السوري',
                'budget_max' => 400,
                'trip_pace' => 'slow',
                'preferred_activity_level' => 'sensible',
                'day_count' => 4,
                'start_date' => '2024-08-01',
                'total_estimated_cost' => 200,
            ],
            [
                'user_id' => 3, // محمد سليمان
                'title' => 'رحلة تدمر التاريخية',
                'budget_max' => 600,
                'trip_pace' => 'intensive',
                'preferred_activity_level' => 'vigour',
                'day_count' => 2,
                'start_date' => '2024-09-10',
                'total_estimated_cost' => 120,
            ],
        ];

        foreach ($trips as $trip) {
            Trip::create($trip);
        }
    }
}
