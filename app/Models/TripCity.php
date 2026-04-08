<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripCity extends Model
{
    //
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
