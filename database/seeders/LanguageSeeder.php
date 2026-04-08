<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $langs = [
            ['code' => 'ar', 'name' => 'العربية'],
            ['code' => 'en', 'name' => 'الإنكليزية'],
            ['code' => 'fr', 'name' => 'الفرنسية'],
            ['code' => 'ru', 'name' => 'الروسية'],
        ];

        foreach ($langs as $lang) {
            Language::create($lang);
        }
    }
}
