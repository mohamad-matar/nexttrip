<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideBooking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = GuideBooking::with(['tourist:id,name', 'guide.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return api_success($bookings);
    }

    public function show(GuideBooking $booking)
    {
        $booking->load(['tourist:id,name', 'guide.user:id,name', 'trip']);
        return api_success($booking);
    }
}
