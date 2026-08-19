<?php

namespace Database\Factories;

use App\Enums\GuideBookingStatus;
use App\Models\GuideBooking;
use App\Models\GuideBookingLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuideBookingLog>
 */
class GuideBookingLogFactory extends Factory
{
    protected $model = GuideBookingLog::class;

    public function definition(): array
    {
        return [
            'booking_id' => GuideBooking::factory(),
            'old_status' => GuideBookingStatus::Pending,
            'new_status' => GuideBookingStatus::Accepted,
            'actor_id' => null,
            'note' => null,
        ];
    }
}
