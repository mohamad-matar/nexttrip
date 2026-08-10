<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingReview;
use App\Models\Place;
use App\Models\PlaceReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * تقييمات المرشدين - أحدث السجلات + بحث + ترتيب + إحصائيات + ترقيم.
     */
    public function guideReviews(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'date');
        $order = $request->query('order', 'desc');

        $query = BookingReview::query()->with([
            'booking:id,tourist_id,guide_id,trip_id,start_date,day_count,status,total_price,created_at',
            'booking.guide:id,user_id,avatar,daily_price',
            'booking.guide.user:id,name,email',
            'booking.tourist:id,name,email',
            'booking.trip:id,title,start_date,end_date',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking.guide.user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('booking.tourist', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($sort === 'rating') {
            $query->orderBy('rating', $order);
        } else {
            $query->orderBy('created_at', $order);
        }

        $reviews = $query->paginate($request->query('per_page', 10));
        $stats = $this->guideReviewStats();

        return api_success([
            'stats' => $stats,
            'items' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    public function showGuideReview(BookingReview $bookingReview)
    {
        $review = $bookingReview->load([
            'booking:id,tourist_id,guide_id,trip_id,start_date,day_count,status,total_price,created_at,last_note',
            'booking.guide:id,user_id,avatar,daily_price,bio',
            'booking.guide.user:id,name,email',
            'booking.tourist:id,name,email',
            'booking.trip:id,title,start_date,end_date',
        ]);       

        return api_success($review);
    }

    public function placeReviews(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'date');
        $order = $request->query('order', 'desc');

        $query = PlaceReview::query()->with([
            'place:id,city_id,category_id,name,average_rating,reviews_count',
            'place.city:id,name',
            'place.category:id,name',
            'place.images:id,place_id,image_url,order',
            'user:id,name,email',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('place', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($sort === 'rating') {
            $query->orderBy('rating', $order);
        } else {
            $query->orderBy('created_at', $order);
        }

        $reviews = $query->paginate($request->query('per_page', 10));
        $stats = $this->placeReviewStats();

        $reviews->getCollection()->transform(function ($review) {
            $review->place_image = $review->place ? $review->place->image_url : null;
            return $review;
        });

        return api_success([
            'stats' => $stats,
            'items' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    public function showPlaceReview(Place $place)
    {
        $review = PlaceReview::load([
            'place:id,city_id,category_id,name,description,average_rating,reviews_count',
            'place.city:id,name',
            'place.category:id,name',
            'place.images:id,place_id,image_url,order',
            'user:id,name,email',
        ]);

        $review->place_image = $review->place ? $review->place->image_url : null;

        return api_success($review);
    }

    private function guideReviewStats()
    {
        $all = BookingReview::all();

        return [
            'total_reviews' => $all->count(),
            'average_rating' => round($all->avg('rating') ?? 0, 1),
            'distribution' => [
                1 => $all->where('rating', 1)->count(),
                2 => $all->where('rating', 2)->count(),
                3 => $all->where('rating', 3)->count(),
                4 => $all->where('rating', 4)->count(),
                5 => $all->where('rating', 5)->count(),
            ],
        ];
    }

    private function placeReviewStats()
    {
        $all = PlaceReview::all();

        return [
            'total_reviews' => $all->count(),
            'average_rating' => round($all->avg('rating') ?? 0, 1),
            'distribution' => [
                1 => $all->where('rating', 1)->count(),
                2 => $all->where('rating', 2)->count(),
                3 => $all->where('rating', 3)->count(),
                4 => $all->where('rating', 4)->count(),
                5 => $all->where('rating', 5)->count(),
            ],
        ];
    }
}
