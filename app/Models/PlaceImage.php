<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'place_id',
    'image_url'
])]
class PlaceImage extends Model
{
    public function place()
    {
        return $this->belongsTo(Place::class);
    }
}
