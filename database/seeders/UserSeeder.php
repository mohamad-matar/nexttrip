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
            // آدمن
            [
                'name' => 'مدير النظام',
                'email' => 'admin@nexttrip.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ],
            // سائحون
            [
                'name' => 'أمير',
                'email' => 'braa@test.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'سارة أحمد',
                'email' => 'sara@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'محمد خالد',
                'email' => 'mohammed@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'فاطمة علي',
                'email' => 'fatima@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'يوسف عمر',
                'email' => 'yousef@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            // مرشدون
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
