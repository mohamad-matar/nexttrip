<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Guide;

class GuideSeeder extends Seeder
{
    public function run(): void
    {
        $guides = [
            [
                'user_id' => 2,
                'price_per_day' => 50,
                'bio' => 'مرشد سياحي بخبرة 10 سنوات في دمشق القديمة.'
            ],
            [
                'user_id' => 3,
                'price_per_day' => 40,
                'bio' => 'مرشد متخصص في الآثار في مدينة حلب.'
            ],
            [
                'user_id' => 4,
                'price_per_day' => 45,
                'bio' => 'مرشد سياحي في الساحل السوري، خبرة في اللاذقية وطرطوس.'
            ],
        ];

        foreach ($guides as $guide) {
            Guide::create($guide);
        }
    }
}
