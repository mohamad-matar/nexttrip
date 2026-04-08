<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        /** basic */
        $this->call([
            CitySeeder::class,
            PlaceTypeSeeder::class,
            LanguageSeeder::class
        ]);

        /** places */
        $this->call([
            PlaceSeeder::class,
            PlaceImageSeeder::class,
        ]);

        /** guides */
        $this->call([
            UserSeeder::class,
            GuideSeeder::class,
            GuideLanguageSeeder::class,
            GuideAvailabilitySeeder::class,
        ]);

        /** tourists related data */
        $this->call([
            UserPreferenceSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
