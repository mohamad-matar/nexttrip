<?php

namespace App\Models;

use App\Enums\GuideBookingStatus;
use App\Observers\GuideBookingObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'tourist_id',
    'guide_id',
    'trip_id',
    'start_date',
    'day_count',
    'description',
    'status',
    'total_price',
])]
#[ObservedBy([GuideBookingObserver::class])]
class GuideBooking extends Model
{

    protected $casts = [
        'start_date'  => 'date',
        'status' => GuideBookingStatus::class,
    ];

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(User::class , 'tourist_id');
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(BookingReview::class, 'booking_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(GuideBookingLog::class, 'booking_id');
    }
}
