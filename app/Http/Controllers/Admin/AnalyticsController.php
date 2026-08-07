<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideBooking;
use App\Models\Place;
use App\Models\SuggestedPlace;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{

    public function index()
    {
        return api_success([
            'overview' => $this->getOverviewStats(),
            'users' => $this->getUserStats(),
            'places' => $this->getPlaceStats(),
            'revenue' => $this->getRevenueStats(),
            'trips' => $this->getTripStats(),
            'bookings' => $this->getBookingStats(),
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
            'new_users_this_month' => User::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)->count(),
            'new_users_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
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
        ];
    }

    private function getPlaceStats()
    {
        return [
            'by_category' => Place::selectRaw('categories.name as category, COUNT(places.id) as count')
            ->leftJoin('categories', 'places.category_id', '=', 'categories.id')
            ->groupBy(DB::raw('categories.name')) 
            ->toBase()
            ->get()->toArray(),
            
            'by_city' => Place::selectRaw('cities.name as city, COUNT(places.id) as count')
            ->leftJoin('cities', 'places.city_id', '=', 'cities.id')
            ->groupBy(DB::raw('cities.name'))
            ->toBase()
            ->get()->toArray(),
            
            'total' => Place::count(),
            'average_rating' => (float) (Place::avg('average_rating') ?? 0),
            'reviews_count' => (int) (Place::sum('reviews_count') ?? 0),
        ];
    }

    private function getRevenueStats()
    {
        $startDate = now()->subMonths(11)->startOfMonth();
        // إنشاء نسخة من الاستعلام الأساسي للحسابات السريعة لعدم التكرار
        $baseQuery = GuideBooking::where('status', 'completed');

        return [
            'total_revenue' => (float) ($baseQuery->sum('total_price') * 0.1 ?? 0),
            'last_year_revenue' => (float) ($baseQuery->where('created_at', '>=', $startDate)->sum('total_price') * 0.1 ?? 0),
            'average_booking_value' => (float) ($baseQuery->avg('total_price') ?? 0),
        ];
    }

    private function getBookingStats()
    {

        $dbData = GuideBooking::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth()) // تغطية الستة أشهر الحالية بالكامل
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->toBase()
            ->get()
            ->mapWithKeys(function ($item) {
                // إنشاء مفتاح فريد لسهولة الدمج اللاحق مثل: "2026-3"
                return ["{$item->year}-{$item->month}" => $item->count];
            })->toArray();

        $chart = [];

        // 2. توليد الأشهر الستة الماضية بدقة وبناء الهيكل المتناسق مع اسم الشهر
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;
            $key = "{$year}-{$month}";

            $chart[] = [
                'month' => $month,
                'year' => $year,
                'count' => $dbData[$key] ?? 0, // إذا لم يكن هناك حجوزات، نضع القيمة 0 تلقائياً
            ];
        }

        return  $chart;
    }


    private function getTripStats()
    {        
        // جلب البيانات الخام للرحلات من قاعدة البيانات لمنع تكرار الشهور الناقصة
        $dbData = Trip::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->toBase()
            ->get()
            ->mapWithKeys(function ($item) {
                return ["{$item->year}-{$item->month}" => $item->count];
            })->toArray();

        $chart = [];

        // ملء الأشهر الستة الماضية لضمان تجانس الشارت
        for ($i =  11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;
            $key = "{$year}-{$month}";

            $chart[] = [
                'month' => $month,
                'month_name' => $date->translatedFormat('F'),
                'year' => $year,
                'count' => $dbData[$key] ?? 0,
            ];
        }
        return $chart;
    }

}
