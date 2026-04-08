<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id',
    'bio',
    'price_per_day'
])]
class Guide extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class);
    }

    public function availability()
    {
        return $this->hasMany(GuideAvailability::class);
    }
}
