<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'دمشق', 'description' => 'العاصمة السورية وأقدم مدينة مأهولة في التاريخ.'],
            ['name' => 'حلب', 'description' => 'مدينة تاريخية عريقة تشتهر بالقلعة والأسواق القديمة.'],
            ['name' => 'اللاذقية', 'description' => 'مدينة ساحلية على البحر المتوسط.'],
            ['name' => 'طرطوس', 'description' => 'مدينة ساحلية هادئة ومناسبة للعائلات.'],
            ['name' => 'حمص', 'description' => 'مدينة وسط سوريا وتشتهر بقلعة الحصن.'],
            ['name' => 'حماة', 'description' => 'مدينة النواعير الشهيرة.'],
            ['name' => 'السويداء', 'description' => 'مدينة جبلية هادئة تشتهر بالآثار الرومانية.'],
            ['name' => 'تدمر', 'description' => 'مدينة أثرية عالمية ذات أهمية تاريخية كبيرة.'],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}
