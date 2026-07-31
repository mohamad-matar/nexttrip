<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use Illuminate\Http\Request;

class MapPlaceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude,radius'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude,radius'],
            'radius' => ['nullable', 'numeric', 'min:0.1', 'max:50', 'required_with:latitude,longitude'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'min_cost' => ['nullable', 'numeric', 'min:0'],
            'max_cost' => ['nullable', 'numeric', 'gte:min_cost'],
        ]);

        $places = Place::query()->with(['city', 'category', 'images', 'interests'])
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
            ->when($filters['min_cost'] ?? null, fn ($query, $cost) => $query->where('cost', '>=', $cost))
            ->when($filters['max_cost'] ?? null, fn ($query, $cost) => $query->where('cost', '<=', $cost))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = "%{$term}%";
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('city', fn ($query) => $query->where('name', 'like', $like))
                        ->orWhereHas('category', fn ($query) => $query->where('name', 'like', $like));
                });
            });

        if (isset($filters['latitude'], $filters['longitude'], $filters['radius'])) {
            $latitude = $filters['latitude'];
            $longitude = $filters['longitude'];
            $radius = $filters['radius'];
            $distance = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';
            $places->select('places.*')->selectRaw("{$distance} as distance_km", [$latitude, $longitude, $latitude])
                ->having('distance_km', '<=', $radius)->orderBy('distance_km');
        }

        return api_success(PlaceResource::collection($places->get()));
    }

    public function show(Place $place)
    {
        return api_success(new PlaceResource($place->load(['city', 'category', 'images', 'interests', 'reviews'])));
    }
}
