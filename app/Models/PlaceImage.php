<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable([
    'place_id',
    'image_url',
    'order'
])]
class PlaceImage extends Model
{
    protected $appends = ['image_url_full'];

    protected function imageUrlFull(): Attribute
    {
        return Attribute::make(function () {
            if (empty($this->image_url)) {
                return null;
            }
            return asset('storage/places/' . $this->image_url);
        });
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }
}
