<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

use App\Http\Resources\GuideResource;

use App\Models\Guide;


use Illuminate\Http\Request;

use function PHPUnit\Framework\isArray;

class GuideController extends Controller
{

    public function index(Request $request)
    {
        $guides = Guide::query()
            ->with([
                'user:id,name',
                'cities:id,name',
                'languages:id,name',
                'bookings.review:id,booking_id,rating'
            ])
            ->withCount('bookings')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->filter($request->only([
                'cities',
                'languages',
                'price',
                'status',
                'sort',
            ]))
            ->latest()
            ->paginate(12);
        return api_success(
            GuideResource::collection($guides),
            "Guides"
        );
    }

    public function show(Guide $guide)
    {

        $guide->load([
            'user',
            'languages',
            'cities',
        ])
            ->loadCount([
                'bookings',
                'reviews',
            ])
            ->loadAvg('reviews', 'rating');

        return api_success(
            new GuideResource($guide),
            "guide details"
        );
    }
}
