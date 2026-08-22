<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_date' => $this->start_date?->toDateString(),
            // end_date حقل محسوب: start_date + (day_count - 1)
            'end_date' => ($this->start_date && $this->day_count)
                ? $this->start_date->copy()->addDays(max(0, $this->day_count - 1))->toDateString()
                : null,
            // days اسم بديل لـ day_count
            'days' => $this->day_count,
            'day_count' => $this->day_count,
            'budget_max' => $this->budget_max !== null ? (float) $this->budget_max : null,
            'total_cost' => $this->total_estimated_cost !== null ? (float) $this->total_estimated_cost : null,
            'total_estimated_cost' => $this->total_estimated_cost !== null ? (float) $this->total_estimated_cost : null,
            'trip_pace' => $this->trip_pace,
            'preferred_activity_level' => $this->preferred_activity_level,
            'source' => $this->source,
            'places_count' => $this->tripPlaces_count ?? $this->tripPlaces?->count() ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
            'trip_places' => TripPlaceResource::collection($this->whenLoaded('tripPlaces')),
        ];
    }
}
