<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Guide;
use App\Models\City;
use App\Models\GuideReview;
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
                'state' => 'active',
                'gender' => $g['gender'],
                'phone' => $g['phone'],
                'DOB' => $g['DOB'],
                'price_per_day' => $g['price_per_day'],
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


/*--------------------------------------------------------------------------
| 4) إنشاء مراجعات المرشدين (guide_reviews)
|--------------------------------------------------------------------------
*/
        $reviewsData = [
            [
                'user_index' => 1, // أمير
                'guide_index' => 0, // مرشد دمشق
                'rating' => 5,
                'comment' => 'مرشد رائع وذو خبرة كبيرة في دمشق القديمة.'
            ],
            [
                'user_index' => 2, // سارة أحمد
                'guide_index' => 1, // مرشد حلب
                'rating' => 4,
                'comment' => 'جولة ممتعة ومعلومات قيمة عن الآثار.'
            ],
            [
                'user_index' => 3, // محمد خالد
                'guide_index' => 2, // مرشد الساحل
                'rating' => 5,
                'comment' => 'أفضل جولة على الساحل، شخص لطيف جداً.'
            ],
            [
                'user_index' => 4, // فاطمة علي
                'guide_index' => 0, // مرشد دمشق
                'rating' => 3,
                'comment' => 'الجولة جيدة لكن كان هناك بعض التأخير.'
            ],
            [
                'user_index' => 5, // يوسف عمر
                'guide_index' => 1, // مرشد حلب
                'rating' => 5,
                'comment' => 'شرح ممتاز وتفاصيل دقيقة عن تاريخ حلب.'
            ],
        ];

        foreach ($reviewsData as $review) {
            GuideReview::create([
                'user_id' => $users[$review['user_index']]->id,
                'guide_id' => $guidesCreated[$review['guide_index']]->id,
                'rating' => $review['rating'],
                'comment' => $review['comment'],
            ]);
        }
    }
}
