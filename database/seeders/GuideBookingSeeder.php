<?php

namespace Database\Seeders;

use App\Enums\GuideBookingStatus;
use App\Models\GuideBooking;
use App\Models\GuideBookingLog;
use App\Models\BookingReview;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class GuideBookingSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        $bookings = [
            [
                'offset' => -40,
                'day_count' => 3,
                'status' => GuideBookingStatus::Accepted,
                'description' => 'جولة قديمة انتهت منذ فترة.',
                'should_review' => true,
                'cancelled_by_user' => false,
            ],
            [
                'offset' => -10,
                'day_count' => 2,
                'status' => GuideBookingStatus::Accepted,
                'description' => 'جولة انتهت حديثاً.',
                'should_review' => false,
                'cancelled_by_user' => false,
            ],
            [
                'offset' => +20,
                'day_count' => 1,
                'status' => GuideBookingStatus::Accepted,
                'description' => 'جولة مستقبلية بعيدة.',
                'should_review' => false,
                'cancelled_by_user' => false,
            ],
            [
                'offset' => +3,
                'day_count' => 1,
                'status' => GuideBookingStatus::Pending,
                'description' => 'جولة مستقبلية قريبة.',
                'should_review' => false,
                'cancelled_by_user' => false,
            ],
            [
                'offset' => +10,
                'day_count' => 1,
                'status' => GuideBookingStatus::Rejected,
                'description' => 'تم رفض الحجز.',
                'should_review' => false,
                'cancelled_by_user' => false,
            ],
            [
                'offset' => -15,
                'day_count' => 1,
                'status' => GuideBookingStatus::CancelledByTourist,
                'description' => 'تم إلغاء الحجز من قبل المستخدم.',
                'should_review' => false,
                'cancelled_by_user' => true,
            ],
        ];

        $created = [];

        foreach ($bookings as $i => $data) {

            $startDate = $today->copy()->addDays($data['offset']);
            $endDate   = $startDate->copy()->addDays($data['day_count']);

            $createdAt = $startDate->copy()->subDays(rand(2, 5));
            $updatedAt = $createdAt->copy()->addDays(rand(1, 3));

            // dump([
            //     'start_date' => $startDate->toDateTimeString(),
            //     'created_at' => $createdAt->toDateTimeString(),
            //     'updated_at' => $updatedAt->toDateTimeString(),
            // ]);
            
            $cancelDate = $data['cancelled_by_user'] ? $updatedAt->copy() : null;

            /*
            |--------------------------------------------------------------------------
            | تحديد الحالة النهائية (Completed إذا انتهت الرحلة)
            |--------------------------------------------------------------------------
            */
            $finalStatus = $data['status'];

            if (
                $data['status'] === GuideBookingStatus::Accepted &&
                $endDate->isPast()
            ) {
                $finalStatus = GuideBookingStatus::Completed;
            }

            /*
            |--------------------------------------------------------------------------
            | إنشاء الحجز
            |--------------------------------------------------------------------------
            */
            $booking = GuideBooking::create([
                'tourist_id' => 2,
                'guide_id' => 1,
                'start_date' => $startDate,
                'day_count' => $data['day_count'],
                'total_price' => $data['day_count'] * 10,
                'status' => $finalStatus,
                'description' => $data['description'],
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            $created[$i] = $booking;

            /*
            |--------------------------------------------------------------------------
            | إضافة Log حسب الحالة
            |--------------------------------------------------------------------------
            */

            // Pending → لا Log
            if ($data['status'] === GuideBookingStatus::Pending) {
                continue;
            }

            // Accepted → Log الموافقة
            if ($data['status'] === GuideBookingStatus::Accepted) {
                GuideBookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => GuideBookingStatus::Pending,
                    'new_status' => GuideBookingStatus::Accepted,
                    'note' => 'تمت الموافقة على الحجز.',
                    'created_at' => $updatedAt,
                ]);
            }

            // Completed → Log انتهاء الرحلة
            if ($finalStatus === GuideBookingStatus::Completed) {
                GuideBookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => GuideBookingStatus::Accepted,
                    'new_status' => GuideBookingStatus::Completed,
                    'note' => 'انتهت الجولة وتم إكمال الحجز.',
                    'created_at' => $endDate,
                ]);
            }

            // Rejected
            if ($data['status'] === GuideBookingStatus::Rejected) {
                GuideBookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => GuideBookingStatus::Pending,
                    'new_status' => GuideBookingStatus::Rejected,
                    'note' => 'تم رفض الحجز.',
                    'created_at' => $updatedAt,
                ]);
            }

            // Cancelled by tourist
            if ($data['status'] === GuideBookingStatus::CancelledByTourist) {
                GuideBookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => GuideBookingStatus::Accepted,
                    'new_status' => GuideBookingStatus::CancelledByTourist,
                    'note' => 'إلغاء الحجز من قبل السائح.',
                    'created_at' => $cancelDate,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | إنشاء تقييمات للحجوزات المكتملة فقط
        |--------------------------------------------------------------------------
        */
        $comments = [
            'جولة ممتازة وممتعة.',
            'المرشد كان رائعاً.',
            'تجربة جيدة بشكل عام.',
            'معلومات قيمة جداً.',
            'كانت الجولة أقل من المتوقع.',
        ];

        foreach ($created as $i => $booking) {

            if (
                $booking->status === GuideBookingStatus::Completed &&
                $bookings[$i]['should_review'] === true
            ) {
                BookingReview::create([
                    'booking_id' => $booking->id,
                    'rating' => rand(3, 5),
                    'comment' => $comments[array_rand($comments)],
                ]);
            }
        }
    }
}
