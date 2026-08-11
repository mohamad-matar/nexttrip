<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
use App\Http\Resources\TripResource;
use App\Models\Place;
use App\Models\Trip;
use App\Models\TripPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripPlaceController extends Controller
{
    public function trips(Request $request)
    {
        $trips = $request->user()->trips()
            ->with(['tripPlaces' => fn ($query) => $query->with([
                'place.city', 'place.category', 'place.images', 'place.interests',
            ])->orderBy('day_number')->orderBy('order')])
            ->latest()
            ->get();

        return api_success(TripResource::collection($trips));
    }

    public function show(Request $request, Trip $trip)
    {
        abort_unless($trip->user_id === $request->user()->id, 403);

        $trip->load(['tripPlaces' => fn ($query) => $query->with([
            'place.city', 'place.category', 'place.images', 'place.interests',
        ])->orderBy('day_number')->orderBy('order')]);

        return api_success(new TripResource($trip));
    }

    public function createTrip(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'trip_pace' => ['nullable', 'string', 'in:slow,medium,intensive'],
            'place_id' => ['nullable', 'integer', 'exists:places,id'],
            'day_number' => ['nullable', 'integer', 'min:1'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $trip = DB::transaction(function () use ($request, $data) {
            $trip = Trip::create([
                'user_id' => $request->user()->id,
                'title' => $data['title'],
                'start_date' => $data['start_date'] ?? null,
                'days' => $data['days'] ?? 1,
                'day_count' => $data['days'] ?? 1,
                'budget_max' => $data['budget_max'] ?? null,
                'trip_pace' => $data['trip_pace'] ?? 'medium',
                'total_cost' => 0,
                'total_estimated_cost' => 0,
                'source' => 'manual',
            ]);

            if (! empty($data['place_id'])) {
                $place = Place::findOrFail($data['place_id']);
                $dayNumber = $data['day_number'] ?? 1;
                $order = (int) $trip->tripPlaces()->where('day_number', $dayNumber)->max('order') + 1;

                TripPlace::create([
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
            }

            return $trip;
        });

        return api_success(
            new TripResource($trip->load('tripPlaces.place')),
            'تم إنشاء الرحلة بنجاح.',
            201
        );
    }

    public function store(Request $request, Trip $trip)
    {
        abort_unless($trip->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'day_number' => ['nullable', 'integer', 'min:1', 'max:'.($trip->days ?? $trip->day_count ?? 30)],
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
