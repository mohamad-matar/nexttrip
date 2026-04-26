<?php

namespace Database\Seeders;

use App\Models\Trip;
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
            PriceUnitSeeder::class,
            InterestSeeder::class,
            LanguageSeeder::class,
            TypeSeeder::class
        ]);        

        /** cities- useres-guides */
        $this->call([
            UserGuideCitySeeder::class,
            GuideLanguageSeeder::class,
        ]);
        /** places */
        $this->call([
            PlaceSeeder::class,
            PlaceImageSeeder::class,
            InterestPlaceSeeder::class,
        ]);

        /** trips */
        $this->call([
            TripSeeder::class,
            TripCitySeeder::class,
            TripPlaceSeeder::class,
        ]);

        /** tourists related data */
        $this->call([
            PlaceReviewSeeder::class,
            InterestUserSeeder::class,
        ]);
        $this->call([
            TripSeeder::class, //empty
            TripPlaceSeeder::class, //empty
        ]);
    }
}
