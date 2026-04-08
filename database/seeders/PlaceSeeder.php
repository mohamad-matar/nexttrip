<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Place;

class PlaceSeeder extends Seeder
{
    public function run(): void
    {
        $places = [

            // دمشق
            [
                'city_id' => 1,
                'place_type_id' => 1,
                'name' => 'الجامع الأموي',
                'description' => 'أحد أهم المعالم الإسلامية في دمشق القديمة.',
                'cost' => 0,
                'duration_minutes' => 60,
                'activity_level' => 'خفيف',
                'latitude' => 33.5113,
                'longitude' => 36.3065,
                'is_outdoor' => false,
            ],
            [
                'city_id' => 1,
                'place_type_id' => 5,
                'name' => 'سوق الحميدية',
                'description' => 'أشهر أسواق دمشق القديمة.',
                'cost' => 0,
                'duration_minutes' => 90,
                'activity_level' => 'متوسط',
                'latitude' => 33.5100,
                'longitude' => 36.3050,
                'is_outdoor' => true,
            ],
            [
                'city_id' => 1,
                'place_type_id' => 2,
                'name' => 'مطعم بيت جبري',
                'description' => 'مطعم تراثي في دمشق القديمة.',
                'cost' => 20,
                'duration_minutes' => 90,
                'activity_level' => 'خفيف',
                'latitude' => 33.5090,
                'longitude' => 36.3070,
                'is_outdoor' => false,
            ],

            // حلب
            [
                'city_id' => 2,
                'place_type_id' => 9,
                'name' => 'قلعة حلب',
                'description' => 'من أهم القلاع التاريخية في العالم.',
                'cost' => 5,
                'duration_minutes' => 120,
                'activity_level' => 'متعب',
                'latitude' => 36.1990,
                'longitude' => 37.1610,
                'is_outdoor' => true,
            ],
            [
                'city_id' => 2,
                'place_type_id' => 5,
                'name' => 'سوق المدينة',
                'description' => 'سوق أثري مشهور.',
                'cost' => 0,
                'duration_minutes' => 90,
                'activity_level' => 'متوسط',
                'latitude' => 36.1980,
                'longitude' => 37.1600,
                'is_outdoor' => true,
            ],

            // اللاذقية
            [
                'city_id' => 3,
                'place_type_id' => 8,
                'name' => 'الشاطئ الأزرق',
                'description' => 'شاطئ جميل ومناسب للسباحة.',
                'cost' => 0,
                'duration_minutes' => 120,
                'activity_level' => 'خفيف',
                'latitude' => 35.5230,
                'longitude' => 35.7800,
                'is_outdoor' => true,
            ],
        ];

        foreach ($places as $place) {
            Place::create($place);
        }
    }
}
