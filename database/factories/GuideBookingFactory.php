<?php

namespace Database\Factories;

use App\Enums\GuideBookingStatus;
use App\Models\Guide;
use App\Models\GuideBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuideBooking>
 */
class GuideBookingFactory extends Factory
{
    protected $model = GuideBooking::class;

    public function definition(): array
    {
        $tourist = User::factory()->tourist()->create();
        $guide = Guide::factory()->create();

        return [
            'tourist_id' => $tourist->id,
            'guide_id' => $guide->id,
            'start_date' => fake()->dateTimeBetween('+10 days', '+30 days'),
            'day_count' => fake()->numberBetween(1, 5),
            'description' => fake()->sentence(),
            'status' => GuideBookingStatus::Pending,
            'total_price' => fake()->randomFloat(2, 50, 1000),
            'last_note' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => GuideBookingStatus::Pending]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => GuideBookingStatus::Accepted]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => GuideBookingStatus::Rejected]);
    }

    public function cancelledByTourist(): static
    {
        return $this->state(fn () => ['status' => GuideBookingStatus::CancelledByTourist]);
    }

    public function cancelledByGuide(): static
    {
        return $this->state(fn () => ['status' => GuideBookingStatus::CancelledByGuide]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => GuideBookingStatus::Completed]);
    }
}
