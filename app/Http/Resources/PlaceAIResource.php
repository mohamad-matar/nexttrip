<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaceAIResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'city' => $this->city,
            'category' => $this->category,

            'cost' => $this->cost,
            'cost_level' => $this->cost < 5000 ? 'low' : ($this->cost < 15000 ? 'medium' : 'high'),

            'expected_duration_minutes' => $this->expected_duration_minutes,
            'duration_level' => $this->expected_duration_minutes < 60 ? 'short' : 'long',

            'activity_level' => $this->activity_level,
            'is_outdoor' => $this->is_outdoor,

            'best_seasons' => $this->best_seasons,
            'recommended_times' => $this->recommended_times,
            'opening_hours' => $this->opening_hours,

            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,

            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'images' => $this->images,

            'interests' => InterestResource::collection($this->interests),
        ];
    }
}
