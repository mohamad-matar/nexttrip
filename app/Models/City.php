<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable(['name', 'description', 'image'])]
class City extends Model
{
    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value == null) {
                    return null;
                }
                return asset(asset('storage/cities/' . $value));
            }
        );
    }

    public function places()
    {
        return $this->hasMany(Place::class);
    }
}
