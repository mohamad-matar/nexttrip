<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideBooking;
use App\Models\Place;
use App\Models\SuggestedPlace;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'month'); // week, month, year
        
        return api_success([
            'overview' => $this->getOverviewStats(),
            'users' => $this->getUserStats(),
            'bookings' => $this->getBookingStats($period),
            'places' => $this->getPlaceStats(),
            'trips' => $this->getTripStats($period),
            'revenue' => $this->getRevenueStats($period),
            'suggested_places' => $this->getSuggestedPlacesStats(),
        ]);
    }

    private function getOverviewStats()
    {
        return [
            'total_users' => User::count(),
            'total_guides' => User::where('role', 'guide')->count(),
            'total_tourists' => User::where('role', 'tourist')->count(),
            'total_places' => Place::count(),
            'total_bookings' => GuideBooking::count(),
            'total_trips' => Trip::count(),
            'pending_suggestions' => SuggestedPlace::where('status', 'pending')->count(),
        ];
    }

    private function getUserStats()
    {
        return [
            'by_role' => [
                'admin' => User::where('role', 'admin')->count(),
                'guide' => User::where('role', 'guide')->count(),
                'tourist' => User::where('role', 'tourist')->count(),
            ],
            'by_status' => [
                'active' => User::where('status', 'active')->count(),
                'blocked' => User::where('status', 'blocked')->count(),
                'unavailable' => User::where('status', 'unavailable')->count(),
            ],
            'new_users_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'new_users_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];
    }

    private function getBookingStats($period)
    {
        $query = GuideBooking::query();
        
        $startDate = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $bookingsInPeriod = $query->where('created_at', '>=', $startDate)->get();

        return [
            'total' => GuideBooking::count(),
            'in_period' => $bookingsInPeriod->count(),
            'by_status' => [
                'pending' => GuideBooking::where('status', 'pending')->count(),
                'accepted' => GuideBooking::where('status', 'accepted')->count(),
                'rejected' => GuideBooking::where('status', 'rejected')->count(),
                'completed' => GuideBooking::where('status', 'completed')->count(),
                'cancelled' => GuideBooking::where('status', 'cancelled')->count(),
            ],
            'monthly_trend' => $this->getMonthlyBookingTrend(),
            'weekly_trend' => $this->getWeeklyBookingTrend(),
        ];
    }

    private function getMonthlyBookingTrend()
    {
        return GuideBooking::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subMonths(6))
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get()
        ->map(function ($item) {
            return [
                'month' => $item->month,
                'year' => $item->year,
                'count' => $item->count,
            ];
        });
    }

    private function getWeeklyBookingTrend()
    {
        return GuideBooking::select(
            DB::raw('WEEK(created_at) as week'),
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subWeeks(8))
        ->groupBy('year', 'week')
        ->orderBy('year', 'asc')
        ->orderBy('week', 'asc')
        ->get()
        ->map(function ($item) {
            return [
                'week' => $item->week,
                'year' => $item->year,
                'count' => $item->count,
            ];
        });
    }

    private function getPlaceStats()
    {
        return [
            'total' => Place::count(),
            'by_category' => Place::select('category_id')
                ->selectRaw('COUNT(*) as count')
                ->with('category:id,name')
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->category?->name ?? 'غير مصنف',
                        'count' => $item->count,
                    ];
                }),
            'by_city' => Place::select('city_id')
                ->selectRaw('COUNT(*) as count')
                ->with('city:id,name')
                ->groupBy('city_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'city' => $item->city?->name ?? 'غير مصنف',
                        'count' => $item->count,
                    ];
                }),
            'average_rating' => Place::avg('average_rating') ?? 0,
            'total_reviews' => Place::sum('reviews_count') ?? 0,
        ];
    }

    private function getTripStats($period)
    {
        $startDate = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        return [
            'total' => Trip::count(),
            'in_period' => Trip::where('created_at', '>=', $startDate)->count(),
            'monthly_trend' => Trip::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'year' => $item->year,
                    'count' => $item->count,
                ];
            }),
        ];
    }

    private function getRevenueStats($period)
    {
        $startDate = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $bookings = GuideBooking::where('status', 'completed')
            ->where('created_at', '>=', $startDate);

        return [
            'total_revenue' => $bookings->sum('total_price') ?? 0,
            'in_period' => $bookings->where('created_at', '>=', $startDate)->sum('total_price') ?? 0,
            'average_booking_value' => $bookings->avg('total_price') ?? 0,
            'monthly_revenue' => GuideBooking::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'year' => $item->year,
                    'revenue' => $item->revenue ?? 0,
                ];
            }),
        ];
    }

    private function getSuggestedPlacesStats()
    {
        return [
            'total' => SuggestedPlace::count(),
            'by_status' => [
                'pending' => SuggestedPlace::where('status', 'pending')->count(),
                'approved' => SuggestedPlace::where('status', 'approved')->count(),
                'rejected' => SuggestedPlace::where('status', 'rejected')->count(),
            ],
            'pending_this_week' => SuggestedPlace::where('status', 'pending')
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];
    }
}
