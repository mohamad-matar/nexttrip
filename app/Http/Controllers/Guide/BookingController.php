<?php

namespace App\Http\Controllers\Guide;

use App\Enums\GuideBookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\GuideBookingResource;
use App\Models\GuideBooking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index()
    {
        $guide = Auth::user()->guide;

        $bookings = GuideBooking::where('guide_id', $guide->id)
            ->with(['user', 'logs'])
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
