<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'trip_id',
    'place_id',
    'city_id',

    'day_number',
    'order',
    'start_time',

    'duration_minutes',
    'travel_minutes',
    'travel_time_minutes',

    'estimated_cost',
    'note',
])]
class TripPlace extends Model
{
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
