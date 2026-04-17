<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserInterest;

class UserInterestSeeder extends Seeder
{
    public function run(): void
    {
        // أمير (سائح عادي) - يحب التاريخ والطعام والتسوق
        UserInterest::create([
            'user_id' => 2,
            'interests' => [1, 4, 5], // تاريخ، طعام ومطاعم، تسوق
            'trip_pace' => 'متوسط',
            'preferred_activity_level' => 'متوسط',
            'budget_min' => 100,
            'budget_max' => 500,
        ]);

        // سارة (سائحة) - تحب الثقافة والفنون والتصوير
        UserInterest::create([
            'user_id' => 3,
            'interests' => [3, 8, 10], // ثقافة وفنون، تصوير، موسيقى وحفلات
            'trip_pace' => 'بطيء',
            'preferred_activity_level' => 'خفيف',
            'budget_min' => 200,
            'budget_max' => 800,
        ]);

        // محمد (سائح) - يحب الطبيعة والمغامرات والرياضة
        UserInterest::create([
            'user_id' => 4,
            'interests' => [2, 9, 7], // طبيعة ومغامرات، رياضة، عائلي وأطفال
            'trip_pace' => 'مكثف',
            'preferred_activity_level' => 'متعب',
            'budget_min' => 50,
            'budget_max' => 300,
        ]);

        // فاطمة (سائحة) - تحب التسوق والطعام والعائلة
        UserInterest::create([
            'user_id' => 5,
            'interests' => [5, 4, 7], // تسوق، طعام ومطاعم، عائلي وأطفال
            'trip_pace' => 'متوسط',
            'preferred_activity_level' => 'خفيف',
            'budget_min' => 150,
            'budget_max' => 600,
        ]);

        // يوسف (سائح) - يحب التاريخ والآثار والتصوير
        UserInterest::create([
            'user_id' => 6,
            'interests' => [1, 8, 6], // تاريخ وآثار، تصوير، ديني وروحي
            'trip_pace' => 'بطيء',
            'preferred_activity_level' => 'متوسط',
            'budget_min' => 80,
            'budget_max' => 400,
        ]);
    }
}
