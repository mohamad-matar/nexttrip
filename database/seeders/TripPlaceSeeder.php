<?php

namespace Database\Seeders;

use App\Models\TripPlace;
use Illuminate\Database\Seeder;

class TripPlaceSeeder extends Seeder
{
    public function run(): void
    {
        $tripPlaces = [
            // رحلة دمشق التراثية (Trip ID: 1)
            [
                'trip_id' => 1,
                'place_id' => 1,
                'day_number' => 1,
                'order' => 1,
                'start_time' => '09:00',
                'duration_minutes' => 90,
                'travel_minutes' => 0,
                'estimated_cost' => 0,
                'note' => 'زيارة صباحية للحديقة',
            ],
            [
                'trip_id' => 1,
                'place_id' => 2,
                'day_number' => 1,
                'order' => 2,
                'start_time' => '12:30',
                'duration_minutes' => 75,
                'travel_minutes' => 30,
                'estimated_cost' => 15,
                'note' => 'غداء في مطعم تراثي',
            ],
            // رحلة حلب الأثرية (Trip ID: 2)
            [
                'trip_id' => 2,
                'place_id' => 4,
                'day_number' => 1,
                'order' => 1,
                'start_time' => '09:00',
                'duration_minutes' => 120,
                'travel_minutes' => 0,
                'estimated_cost' => 3,
                'note' => 'زيارة قلعة حلب',
            ],
            [
                'trip_id' => 2,
                'place_id' => 3,
                'day_number' => 1,
                'order' => 2,
                'start_time' => '14:00',
                'duration_minutes' => 60,
                'travel_minutes' => 20,
                'estimated_cost' => 5,
                'note' => 'استراحة في المقهى',
            ],
            // رحلة الساحل السوري (Trip ID: 3)
            [
                'trip_id' => 3,
                'place_id' => 5,
                'day_number' => 1,
                'order' => 1,
                'start_time' => '10:00',
                'duration_minutes' => 180,
                'travel_minutes' => 0,
                'estimated_cost' => 10,
                'note' => 'يوم على الشاطئ',
            ],
            [
                'trip_id' => 3,
                'place_id' => 6,
                'day_number' => 2,
                'order' => 1,
                'start_time' => '09:00',
                'duration_minutes' => 150,
                'travel_minutes' => 60,
                'estimated_cost' => 8,
                'note' => 'شاطئ هادئ للعائلات',
            ],
            // رحلة تدمر التاريخية (Trip ID: 4)
            [
                'trip_id' => 4,
                'place_id' => 9,
                'day_number' => 1,
                'order' => 1,
                'start_time' => '08:00',
                'duration_minutes' => 180,
                'travel_minutes' => 0,
                'estimated_cost' => 10,
                'note' => 'استكشاف آثار تدمر',
            ],
            [
                'trip_id' => 4,
                'place_id' => 9,
                'day_number' => 2,
                'order' => 1,
                'start_time' => '08:00',
                'duration_minutes' => 180,
                'travel_minutes' => 0,
                'estimated_cost' => 10,
                'note' => 'استكمال زيارة الآثار',
            ],
        ];

        foreach ($tripPlaces as $tripPlace) {
            TripPlace::create($tripPlace);
        }
    }
}
