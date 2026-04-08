<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'name',
    'description',
    'city_id',
    'place_type_id',
    'price_min',
    'price_max'
])]
class Place extends Model
{
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function type()
    {
        return $this->belongsTo(PlaceType::class, 'place_type_id');
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
