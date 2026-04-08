<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'أمل',
                'email' => 'amal@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'مرشد دمشق',
                'email' => 'guide.damascus@example.com',
                'password' => Hash::make('password'),
                'role' => 'guide',
            ],
            [
                'name' => 'مرشد حلب',
                'email' => 'guide.aleppo@example.com',
                'password' => Hash::make('password'),
                'role' => 'guide',
            ],
            [
                'name' => 'مرشد الساحل',
                'email' => 'guide.coast@example.com',
                'password' => Hash::make('password'),
                'role' => 'guide',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
