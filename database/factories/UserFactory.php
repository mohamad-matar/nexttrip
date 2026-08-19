<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Tourist,
            'status' => UserStatus::Active,
        ];
    }

    public function tourist(): static
    {
        return $this->state(fn () => ['role' => UserRole::Tourist]);
    }

    public function guide(): static
    {
        return $this->state(fn () => ['role' => UserRole::Guide]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Blocked]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Closed]);
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Unavailable]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
