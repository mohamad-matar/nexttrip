<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideResource extends JsonResource
{
    public function toArray($request)
    {       
        return [
            'guide_id'     => $this->id,
            'name'         => $this->user->name,

            'avatar'       => asset('storage/avatars/' . ($this->avatar ?? "no-image.png")),
            
            'age'          => Carbon::parse($this->DOB)->age,
            'gender'          => $this->gender,
            
            'daily_price'  => $this->daily_price,
            'bio'          => $this->bio,

            // المحافظات كسلسلة نصية
            'cities'       => $this->whenLoaded('cities', function () {
                return $this->cities->pluck('name')->implode(', ');
            }),

            // اللغات كسلسلة نصية
            'languages'    => $this->whenLoaded('languages', function () {
                return $this->languages->pluck('name')->implode(', ');
            }),

            'bookings_count' => $this->bookings_count,
            'reviews_count' => $this->reviews_count,
            // متوسط التقييم الحقيقي من الحجوزات
            'rating'       => round($this->reviews_avg_rating, 2),
        ];
    }
}
