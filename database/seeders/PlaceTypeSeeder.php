<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlaceType;

class PlaceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'موقع أثري',
            'مطعم',
            'مقهى',
            'حديقة',
            'سوق',
            'متحف',
            'فعالية',
            'شاطئ',
            'قلعة',
            'مركز تسوق',
        ];

        foreach ($types as $type) {
            PlaceType::create(['name' => $type]);
        }
    }
}
    