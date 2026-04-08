<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'guide_id',
    'date',
    'is_available'
])]
class GuideAvailability extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }
}
