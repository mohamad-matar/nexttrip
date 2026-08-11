<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripPlaceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'day_number' => $this->day_number,
            'order' => $this->order,
            'start_time' => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'travel_minutes' => $this->travel_minutes,
            'estimated_cost' => (float) $this->estimated_cost,
            'note' => $this->note,
            'place' => $this->whenLoaded('place', fn () => $this->resolvePlace($this->place)),
        ];
    }

    private function resolvePlace($place): ?array
    {
        if (! $place) {
            return null;
        }

        return [
            'id' => $place->id,
            'name' => $place->name,
            'city_id' => $place->city_id,
            'category_id' => $place->category_id,
            'description' => $place->description,
            'address' => $place->address,
            'cost' => $place->cost !== null ? (float) $place->cost : null,
            'average_rating' => $place->average_rating !== null ? (float) $place->average_rating : null,
            'reviews_count' => $place->reviews_count,
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
            'city' => $place->city ? ['id' => $place->city->id, 'name' => $place->city->name] : null,
            'category' => $place->category ? ['id' => $place->category->id, 'name' => $place->category->name] : null,
            'image' => $place->images?->first()?->image_url ?? null,
        ];
    }
}
