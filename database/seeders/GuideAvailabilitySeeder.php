<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GuideAvailability;

class GuideAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        // 10 أيام قادمة لكل مرشد
        for ($guide = 1; $guide <= 3; $guide++) {
            for ($i = 0; $i < 10; $i++) {
                GuideAvailability::create([
                    'guide_id' => $guide,
                    'date' => now()->addDays($i)->format('Y-m-d'),
                    'is_available' => rand(0, 1),
                ]);
            }
        }
    }
}
