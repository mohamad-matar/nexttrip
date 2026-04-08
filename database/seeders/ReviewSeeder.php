<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'user_id' => 1,
                'place_id' => 1,
                'rating' => 5,
                'comment' => 'مكان رائع جداً ومليء بالروحانية.'
            ],
            [
                'user_id' => 1,
                'place_id' => 2,
                'rating' => 4,
                'comment' => 'سوق جميل ومزدحم، تجربة ممتعة.'
            ],
            [
                'user_id' => 2,
                'place_id' => 4,
                'rating' => 5,
                'comment' => 'القلعة مذهلة وتستحق الزيارة.'
            ],
        ];

        foreach ($reviews as $review) {
            Review::create($review);
        }
    }
}
