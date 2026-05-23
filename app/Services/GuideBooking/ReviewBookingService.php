<?php

namespace App\Services\GuideBooking;

use App\Enums\GuideBookingStatus;
use App\Exceptions\BadDataException;
use App\Models\BookingReview;
use App\Models\GuideBooking;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReviewBookingService
{
    public function handle(Request $request, GuideBooking $booking)
    {
        Gate::authorize('review', $booking);

        $tripEnd = $booking->start_date->copy()->addDays($booking->day_count);

        //التأكد من عدم وجود مراجعة سابقة
        $reviewExists = BookingReview::where('booking_id', $booking->id)->exists();
        if ($reviewExists)
            throw new BadDataException('You can review only onetime');


        $data = $request->validate([
            'rating'  => ['required', 'in:1,2,3,4,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['booking_id'] = $booking->id;
        BookingReview::create($data);
    }
}
