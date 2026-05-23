<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GuideReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'user'    => $this->user->name,
            'rating'  => $this->rating,
            'comment' => $this->comment,
            'date'    => $this->created_at->format('Y-m-d'),
        ];
    }
}
