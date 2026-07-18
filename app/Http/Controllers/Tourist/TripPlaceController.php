<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Models\Trip;
use App\Models\TripPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripPlaceController extends Controller
{
    public function trips(Request $request)
    {
        $trips = $request->user()->trips()->latest()->get([
            'id', 'title', 'start_date', 'end_date', 'days', 'total_cost', 'created_at',
        ]);

        return api_success($trips);
    }

    public function store(Request $request, Trip $trip)
    {
        abort_unless($trip->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'day_number' => ['nullable', 'integer', 'min:1', 'max:' . $trip->days],
            'start_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $place = Place::findOrFail($data['place_id']);
        $dayNumber = $data['day_number'] ?? 1;

        $tripPlace = DB::transaction(function () use ($trip, $place, $data, $dayNumber) {
            $order = (int) $trip->tripPlaces()->where('day_number', $dayNumber)->max('order') + 1;

            return TripPlace::create([
                'trip_id' => $trip->id,
                'place_id' => $place->id,
                'day_number' => $dayNumber,
                'order' => $order,
                'start_time' => $data['start_time'] ?? '09:00',
                'duration_minutes' => $place->expected_duration_minutes ?? 60,
                'travel_minutes' => 0,
                'estimated_cost' => $place->cost ?? 0,
                'note' => $data['note'] ?? null,
            ]);
        });

        return api_success([
            'trip_place' => $tripPlace->load('place'),
            'place' => new PlaceResource($place->load(['city', 'category', 'images', 'interests'])),
        ], 'تمت إضافة المكان إلى الرحلة.', 201);
    }
}
