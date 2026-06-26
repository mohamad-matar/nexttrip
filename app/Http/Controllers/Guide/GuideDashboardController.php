<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuideDashboardResource;
use Illuminate\Support\Facades\Auth;

class GuideDashboardController extends Controller
{
    public function index()
    {
        $guide = Auth::user()->guide;

        // الحجوزات المؤكدة
        $confirmedBookings = $guide->bookings()
            ->where('status', 'confirmed')
            ->get();

        // عدد الأيام المحجوزة
        $bookedDays = $confirmedBookings->sum(function ($booking) {
            return $booking->start_date->diffInDays($booking->end_date) + 1;
        });

        // عدد الرحلات المؤكدة
        $confirmedTrips = $confirmedBookings->count();

        // الطلبات الجديدة
        $newRequests = $guide->bookings()
            ->where('status', 'pending')
            ->count();

        // آخر التقييمات (من جدول booking_reviews عبر guide_bookings)
        $latestReviews = $guide->bookings()
            ->whereHas('review')
            ->with(['review', 'tourist'])
            ->latest()
            ->take(5)
            ->get();

            // آخر الحجوزات
        $latestBookings = $guide->bookings()
            ->with('tourist')
            ->latest()
            ->take(5)
            ->get();

        // أيام التقويم (أيام بداية الرحلات المؤكدة)
        $calendarDays = $confirmedBookings
            ->pluck('start_date')
            ->map(fn($date) => $date->day)
            ->values();

        return api_success(new GuideDashboardResource([
            'guide' => $guide,
            'stats' => [
                'booked_days' => $bookedDays,
                'confirmed_trips' => $confirmedTrips,
                'new_requests' => $newRequests,
            ],
            'latest_reviews' => $latestReviews,
            'latest_bookings' => $latestBookings,
            'calendar' => $calendarDays,
        ]));
    }
}
