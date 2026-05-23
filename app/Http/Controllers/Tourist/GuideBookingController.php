<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;

use App\Http\Requests\GuideBookingRequest;

use App\Http\Resources\GuideBookingResource;

use App\Models\Guide;
use App\Models\GuideBooking;

use App\Services\GuideBooking\BookGuideService;
use App\Services\GuideBooking\CancelBookingService;
use App\Services\GuideBooking\ReviewBookingService;

use Illuminate\Http\Request;


class GuideBookingController extends Controller
{
    public function __construct(
        private readonly BookGuideService $bookGuide,
        private readonly CancelBookingService $cancelBooking,
        private readonly ReviewBookingService $reviewBooking,
    ) {}

    public function index()
    {
        $bookings = GuideBooking::with([
            'guide:id,user_id,avatar',
            'guide.user:id,name',
            'review'
        ])->where('tourist_id', auth()->id())
            ->latest()
            ->get();
        // return $bookings;
        return api_success(GuideBookingResource::collection($bookings), "كافة الحجوزات");
    }

    public function show(GuideBooking $booking)
    {
        $booking = $booking->load([
            'guide:id,user_id,avatar',
            'guide.user:id,name',
            'review',
            'logs'
        ]);

        // return $guideBooking;
        return api_success(new GuideBookingResource($booking), "بيانات الحجز");
    }

    public function book(GuideBookingRequest $request, Guide $guide)
    {
        $booking = $this->bookGuide->handle($request, $guide);

        return api_success(new GuideBookingResource($booking), "تم إضافة الحجز", 201);
    }

    public function cancel(GuideBooking $booking, Request $request)
    {
            
        $booking = $this->cancelBooking->cancelByTourist($booking, $request);

        return api_success(new GuideBookingResource($booking), ['message' => 'تم إلغاء الحجز بنجاح']);
    }

    public function review(Request $request, GuideBooking $booking)
    {
        $this->reviewBooking->handle($request, $booking);

        return api_success(['message' => 'تم إرسال المراجعة.']);
    }
}
