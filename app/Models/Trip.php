<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'title',
    'budget_max',
    'trip_pace',
    'preferred_activity_level',
    'day_count',
    'days',
    'start_date',
    'end_date',
    'total_estimated_cost',
    'total_cost',
    'source',
    'ai_payload',
])]

class Trip extends Model
{
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'ai_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tripPlaces()
    {
        return $this->hasMany(TripPlace::class);
    }
}
