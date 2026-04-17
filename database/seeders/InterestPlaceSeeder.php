<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Place;
use App\Models\Interest;

class InterestPlaceSeeder extends Seeder
{
    public function run(): void
    {
        // الجامع الأموي - تاريخ وآثار، ديني وروحي، تصوير
        $place1 = Place::find(1);
        $place1->interests()->attach([1, 6, 8]);

        // سوق الحميدية - تسوق، تاريخ وآثار، تصوير
        $place2 = Place::find(2);
        $place2->interests()->attach([1, 5, 8]);

        // مطعم بيت جبري - طعام ومطاعم، ثقافة وفنون
        $place3 = Place::find(3);
        $place3->interests()->attach([4, 3]);

        // قلعة حلب - تاريخ وآثار، طبيعة ومغامرات، تصوير
        $place4 = Place::find(4);
        $place4->interests()->attach([1, 2, 8]);

        // سوق المدينة - تسوق، تاريخ وآثار
        $place5 = Place::find(5);
        $place5->interests()->attach([5, 1]);

        // الشاطئ الأزرق - طبيعة ومغامرات، عائلي وأطفال، رياضة
        $place6 = Place::find(6);
        $place6->interests()->attach([2, 7, 9]);
    }
}
