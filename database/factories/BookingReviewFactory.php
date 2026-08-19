<?php

namespace Database\Factories;

use App\Models\BookingReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingReview>
 */
class BookingReviewFactory extends Factory
{
    protected $model = BookingReview::class;

    public function definition(): array
    {
        return [
            'booking_id' => GuideBookingFactory::create(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.7)->sentence(),
        ];
    }
}
