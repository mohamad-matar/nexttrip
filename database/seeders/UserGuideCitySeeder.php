<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Guide;
use App\Models\City;
use Illuminate\Support\Facades\Hash;

class UserGuideCitySeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1) إنشاء المدن
        |--------------------------------------------------------------------------
        */
        $citiesData = [
            [
                'name' => 'دمشق',
                'image' => 'damascus.jpg',
                'description' => 'عاصمة سوريا وأقدم مدينة مأهولة في التاريخ.'
            ],
            [
                'name' => 'حلب',
                'image' => 'aleppo.jpg',
                'description' => 'مدينة تاريخية عريقة تشتهر بالقلعة والأسواق القديمة.'
            ],
            [
                'name' => 'اللاذقية',
                'image' => 'latakia.jpg',
                'description' => 'مدينة ساحلية جميلة على البحر الأبيض المتوسط.'
            ],

            // المدن الجديدة
            [
                'name' => 'طرطوس',
                'image' => 'tartous.jpg',
                'description' => 'مدينة ساحلية هادئة ومناسبة للعائلات.'
            ],
            [
                'name' => 'حمص',
                'image' => 'homs.jpg',
                'description' => 'مدينة وسط سوريا وتشتهر بقلعة الحصن.'
            ],
            [
                'name' => 'حماة',
                'image' => 'hama.jpg',
                'description' => 'مدينة النواعير الشهيرة.'
            ],
            [
                'name' => 'السويداء',
                'image' => 'swaida.jpg',
                'description' => 'مدينة جبلية هادئة تشتهر بالآثار الرومانية.'
            ],
            [
                'name' => 'تدمر',
                'image' => 'palmyra.jpg',
                'description' => 'مدينة أثرية عالمية ذات أهمية تاريخية كبيرة.'
            ],
        ];


        $cities = [];
        foreach ($citiesData as $city) {
            $cities[] = City::create($city);
        }

        /*
        |--------------------------------------------------------------------------
        | 2) إنشاء المستخدمين
        |--------------------------------------------------------------------------
        */
        $usersData = [
            // آدمن
            [
                'name' => 'مدير النظام',
                'email' => 'admin@test.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ],

            // سائحون
            [
                'name' => 'محمد سليمان',
                'email' => 'suliman@test.com',
                'password' => Hash::make('password'),
                'role' => 'tourist',
            ],
            [
                'name' => 'نور كاملة',
                'email' => 'noor@example.com',
                'password' => Hash::make('password'),
                'role' => 'tourist',
            ],
            [
                'name' => 'محمد نور',
                'email' => 'mhd@example.com',
                'password' => Hash::make('password'),
                'role' => 'tourist',
            ],

            // مرشدون
            [
                'name' => 'براء حمود',
                'email' => 'baraa@test.com',
                'password' => Hash::make('password'),
                'role' => 'guide',
            ],
            [
                'name' => 'محمد مطر',
                'email' => 'matar@example.com',
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

        $users = [];
        foreach ($usersData as $user) {
            $users[] = User::create($user);
        }

        /*
        |--------------------------------------------------------------------------
        | 3) إنشاء المرشدين وربطهم بالمدن (Many-to-Many)
        |--------------------------------------------------------------------------
        */

        $guidesData = [
            [
                'user_index' => 4, // مرشد دمشق
                'gender' => 'M',
                'phone' => '0999999999',
                'DOB' => '1985-01-01',
                'price_per_day' => 50,
                'bio' => 'مرشد سياحي بخبرة 10 سنوات في دمشق القديمة.',
                'cities' => [0], // دمشق
            ],
            [
                'user_index' => 5, // مرشد حلب
                'gender' => 'M',
                'phone' => '0988888888',
                'DOB' => '1988-05-12',
                'price_per_day' => 40,
                'bio' => 'مرشد متخصص في الآثار في مدينة حلب.',
                'cities' => [1], // حلب
            ],
            [
                'user_index' => 6, // مرشد الساحل
                'gender' => 'M',
                'phone' => '0977777777',
                'DOB' => '1990-09-20',
                'price_per_day' => 45,
                'bio' => 'مرشد سياحي في الساحل السوري، خبرة في اللاذقية وطرطوس.',
                'cities' => [2], // اللاذقية
            ],
        ];




        $guidesCreated = [];

        foreach ($guidesData as $g) {
            $guide = Guide::create([
                'user_id' => $users[$g['user_index']]->id,
                'gender' => $g['gender'],
                'phone' => $g['phone'],
                'DOB' => $g['DOB'],
                'daily_price' => $g['price_per_day'],
                'bio' => $g['bio'],
            ]);            

            $guidesCreated[] = $guide;

            // ربط المرشد بالمدن
            $guide->cities()->attach(
                collect($g['cities'])->map(
                    fn($i) => $cities[$i]->id
                )
            );
        }


    }
}
