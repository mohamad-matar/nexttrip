<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Interest; // أو Type إذا كان اسم الجدول type
use Illuminate\Support\Facades\DB;

class InterestUserSeeder extends Seeder
{
    public function run(): void
    {
        // مثال: ربط بعض المستخدمين ببعض الاهتمامات
        $data = [
            [
                'user_id' => 2,   // نور كاملة
                'interest_id' => 1, // طبيعة
            ],
            [
                'user_id' => 2,
                'interest_id' => 3, // جبل
            ],
            [
                'user_id' => 3,   // محمد سليمان
                'interest_id' => 2, // بحر
            ],
            [
                'user_id' => 3,  
                'interest_id' => 5, // تسوق
            ],
            [
                'user_id' => 3,  
                'interest_id' => 7, // متاحف
            ],
        ];

        DB::table('interest_user')->insert($data);
    }
}
