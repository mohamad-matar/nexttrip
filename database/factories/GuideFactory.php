<?php

namespace Database\Factories;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    protected $model = Guide::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->guide(),
            'gender' => fake()->randomElement(['M', 'F']),
            'phone' => fake()->numerify('09########'),
            'DOB' => fake()->dateTimeBetween('-40 years', '-25 years')->format('Y-m-d'),
            'avatar' => null,
            'daily_price' => fake()->randomFloat(2, 20, 200),
            'bio' => fake()->paragraph(),
        ];
    }
}
