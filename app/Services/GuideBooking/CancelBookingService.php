<?php

namespace App\Services\GuideBooking;

use App\Enums\GuideBookingStatus;
use App\Models\GuideBooking;
use App\Models\GuideBookingLog;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CancelBookingService
{
    public function cancelByTourist(GuideBooking $booking, Request $request)
    {        
        Gate::authorize('cancelByTourist', $booking);
        
        DB::transaction(function () use ($booking , $request) {
        
            $booking->update([
                'status' => GuideBookingStatus::CancelledByTourist->value,
                'cancel_date' => Carbon::now(),
                'last_note'   => $request->note,
            ]);           
        });

        return $booking->load('logs');
    }
}
