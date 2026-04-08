<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserPreference;

class UserPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        UserPreference::create([
            'user_id' => 1,
            'interests' => json_encode(['مواقع أثرية', 'مطاعم', 'أسواق']),
            'trip_pace' => 'متوسط',
            'budget_min' => 100,
            'budget_max' => 500,
            'preferred_activity_level' => 'متوسط',
        ]);
    }
}
