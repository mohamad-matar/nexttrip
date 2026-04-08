<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name'])]
class PlaceType extends Model
{
    public function places()
    {
        return $this->hasMany(Place::class);
    }
}
