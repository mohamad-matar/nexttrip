<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable([
        'city_id', 
        'name', 'description', 
        'cost', 
        'duration_minutes', 'activity_level', 
        'is_outdoor', 'best_seasons', 'recommended_times', 'opening_hours'  ,
        'average_rating', 'reviews_count',
        'latitude', 'longitude', 
])]
class Place extends Model
{
    protected $casts = [
    'best_seasons' => 'array',
    'recommended_times' => 'array',
];

public function priceUnit()
{
    return $this->belongsTo(PriceUnit::class);
}

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function interests()
    {
        return $this->belongsToMany(Interest::class);
    }

    public function images()
    {
        return $this->hasMany(PlaceImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
