<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Fillable('name')]
#[Table(timestamps: false)]
class PriceUnit extends Model
{

    public function places()
    {
        return $this->hasMany(Place::class);
    }
}