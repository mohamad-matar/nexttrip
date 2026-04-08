<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'description'])]
class City extends Model
{
    public function places()
    {
        return $this->hasMany(Place::class);
    }
}
