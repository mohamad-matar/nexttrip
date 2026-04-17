<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriceUnit;

class PriceUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            'ليرة سورية',
            'دولار أمريكي',
            'يورو',
            'مجاناً',
        ];

        foreach ($units as $unit) {
            PriceUnit::create(['name' => $unit]);
        }
    }
}
