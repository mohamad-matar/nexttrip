<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable([
    'user_id',
    'gender',
    'phone',
    'DOB',
    'avatar',
    'price_per_day',
    'bio',

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
    public function cities()
    {
        return $this->belongsToMany(City::class);
    }
}
