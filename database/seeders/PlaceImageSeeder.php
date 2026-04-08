<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlaceImage;

class PlaceImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            ['place_id' => 1, 'image_url' => 'umayyad1.jpg', 'order' => 1],
            ['place_id' => 1, 'image_url' => 'umayyad2.jpg', 'order' => 2],

            ['place_id' => 2, 'image_url' => 'hamidieh1.jpg', 'order' => 1],

            ['place_id' => 4, 'image_url' => 'aleppo_castle1.jpg', 'order' => 1],

            ['place_id' => 6, 'image_url' => 'blue_beach1.jpg', 'order' => 1],
        ];

        foreach ($images as $img) {
            PlaceImage::create($img);
        }
    }
}
