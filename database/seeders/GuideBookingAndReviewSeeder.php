<?php

namespace Database\Seeders;

use App\Enums\GuideBookingStatus;
use Illuminate\Database\Seeder;
use App\Models\GuideBooking;
use App\Models\BookingReview;
use App\Models\Guide;
use App\Models\User;
use Carbon\Carbon;

class GuideBookingAndReviewSeeder extends Seeder
{
    public function run(): void
    {
        $tourists = User::where('role', 'tourist')->get();
        $guides = Guide::get();

        if ($tourists->isEmpty() || $guides->isEmpty()) {
            $this->command->warn(" لا يوجد سياح أو مرشدون في قاعدة البيانات.");
            return;
        }

        $comments = [
            'جولة ممتازة وممتعة.',
            'المرشد كان رائعاً.',
            'تجربة جيدة بشكل عام.',
            'معلومات قيمة جداً.',
            'كانت الجولة أقل من المتوقع.',
        ];

        /*
        |--------------------------------------------------------------------------
        | إنشاء حجوزات + تقييمات
        |--------------------------------------------------------------------------
        */

        foreach ($guides as $guide) {

            foreach ($tourists as $tourist) {

                // إنشاء 3 حجوزات لكل سائح مع كل مرشد
                for ($i = 0; $i < 3; $i++) {

                    $startDate = Carbon::today()->subDays(rand(1, 60)); 
                    $dayCount = rand(1, 3);

                    $booking = GuideBooking::create([
                        'tourist_id' => $tourist->id,
                        'guide_id' => $guide->id,
                        'trip_id' => null,
                        'start_date' => $startDate,
                        'day_count' => $dayCount,
                        'status' => GuideBookingStatus::Accepted,
                        'description' => 'حجز تجريبي.',
                        'total_price' => rand(50, 200),
                        'last_note' => 'تمت الجولة بنجاح.',
                    ]);

                    // 50% من الحجوزات تحصل على تقييم
                    if (rand(0, 1) === 1) {
                        BookingReview::create([
                            'booking_id' => $booking->id,
                            'rating' => rand(3, 5),
                            'comment' => $comments[array_rand($comments)],
                            'created_at' => now()->subtract('day' , rand(1,10)),
                        ]);
                    }
                }
            }
        }

        $this->command->info(" تم إنشاء حجوزات وتقييمات بنجاح.");
    }
}
