<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;
use App\Models\GuideLanguage;

class GuideLanguageSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // المرشد الأول
            ['guide_id' => 1, 'language_id' => 1], // عربي
            ['guide_id' => 1, 'language_id' => 2], // إنكليزي

            // المرشد الثاني
            ['guide_id' => 2, 'language_id' => 1],
            ['guide_id' => 2, 'language_id' => 3], // فرنسي

            // المرشد الثالث
            ['guide_id' => 3, 'language_id' => 1],
            ['guide_id' => 3, 'language_id' => 4], // روسي
        ];

        foreach ($data as $item) {
            Guide::find($item['guide_id'])->languages()->attach($item['language_id']);
        }
    }
}
