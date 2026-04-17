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
            PriceUnitSeeder::class,
            InterestSeeder::class,
            LanguageSeeder::class
        ]);

        /** places */
        $this->call([
            PlaceSeeder::class,
            PlaceImageSeeder::class,
            InterestPlaceSeeder::class,
        ]);

        /** guides */
        $this->call([
            UserSeeder::class,
            UserInterestSeeder::class,
            GuideSeeder::class,
            GuideLanguageSeeder::class,
            GuideAvailabilitySeeder::class,
        ]);

        /** trips */
        $this->call([
            TripSeeder::class,
            TripCitySeeder::class,
            TripPlaceSeeder::class,
        ]);

        /** tourists related data */
        $this->call([
            ReviewSeeder::class,
        ]);
    }
}
