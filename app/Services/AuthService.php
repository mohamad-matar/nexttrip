<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => $data['role'],
            ]);

            if ($data['role'] === UserRole::Guide) {
                $avatarPath = isset($data['avatar'])
                    ? $data['avatar']->store('avatars')
                    : null;

                Guide::create([
                    'user_id'       => $user->id,
                    'gender'        => $data['gender'],
                    'phone'         => $data['phone'],
                    'DOB'           => $data['DOB'],
                    'price_per_day' => $data['price_per_day'],
                    'bio'           => $data['bio'],
                    'avatar'        => $avatarPath,
                ]);
            }

            $token = $user->createToken("mobile")->plainTextToken;

            return [
                'user'  => $user,
                'token' => $token,
            ];
        });
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            abort(422, "Invalid credentials");
        }

        return [
            'user'  => $user,
            'token' => $user->createToken("mobile")->plainTextToken,
        ];
    }
}
