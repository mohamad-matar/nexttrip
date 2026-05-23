<?php

namespace App\Services\GuideBooking;

use App\Http\Requests\GuideBookingRequest;
use App\Models\Guide;
use App\Models\GuideBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class BookGuideService
{
    public function handle(GuideBookingRequest $request, Guide $guide)
    {

        Gate::authorize(
            'book',
            [
                GuideBooking::class,
                $guide,
                new Carbon($request->start_date),
                $request->day_count
            ]
        );


        $data = $request->validated();

        $data['total_price'] = $request->day_count * $guide->daily_price;
        $data['tourist_id'] = $request->user()->id;
        $data['guide_id'] = $guide->id;

        return GuideBooking::create($data);
    }
}
