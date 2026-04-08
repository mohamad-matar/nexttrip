<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id',
    'title',
    'days',
    'total_cost',
    'ai_raw_response'
])]
class Trip extends Model
{
    protected function casts(): array
    {
        return [
            'ai_raw_response' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function places()
    {
        return $this->hasMany(TripPlace::class);
    }
}
