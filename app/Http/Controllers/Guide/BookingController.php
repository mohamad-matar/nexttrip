<?php

namespace App\Http\Controllers\Guide;

use App\Enums\GuideBookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\GuideBookingResource;
use App\Models\GuideBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $guide = Auth::user()->guide;
        $status = $request->status;
        $bookings = GuideBooking::where('guide_id', $guide->id)
            ->with(['tourist', 'logs'])
            ->status(GuideBookingStatus::tryFrom( $status))
            ->latest()
            ->get();

        return api_success(data: GuideBookingResource::collection($bookings));
    }

    public function accept(GuideBooking $booking)
    {
        Gate::authorize('acceptOrReject', $booking);

        $booking->update(['status' => GuideBookingStatus::Accepted]);

        return api_success();
    }

    public function reject(GuideBooking $booking)
    {
        Gate::authorize('acceptOrReject', $booking);

        $booking->update(['status' => GuideBookingStatus::Rejected]);

        return api_success();
    }

    public function cancel(GuideBooking $booking)
    {
        Gate::authorize('cancelByGuide', $booking);

        $booking->update(['status' => GuideBookingStatus::CancelledByGuide]);

        return api_success();
    }
}
