<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\PlaceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TouristPlaceReviewController extends Controller
{
    /**
     * قائمة تقييمات الأماكن التي أرسلها السائح الحالي.
     */
    public function index()
    {
        $tourist = Auth::user();

        $reviews = PlaceReview::with([
            'place:id,city_id,category_id,name,average_rating,reviews_count',
            'place.city:id,name',
            'place.images:id,place_id,image_url,order',
        ])
            ->where('user_id', $tourist->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // إضافة رابط الصورة كاملة لكل تقييم
        $reviews->transform(function ($review) {
            $review->place_image = $review->place ? $review->place->image_url : null;
            return $review;
        });

        return api_success($reviews);
    }

    /**
     * إضافة/تحديث تقييم مكان من قبل السائح.
     */
    public function store(Request $request, Place $place)
    {
        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $userId = Auth::id();
        $placeId = $place->id;

        $review = PlaceReview::updateOrCreate(
            ['user_id' => $userId, 'place_id' => $placeId],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        return api_success(['review' => $review], 'تم حفظ التقييم بنجاح');
    }
}

