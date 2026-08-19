<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::tourist(),
            'title' => fake()->sentence(3),
            'day_count' => fake()->numberBetween(1, 7),
            'source' => 'manual',
        ];
    }
}
