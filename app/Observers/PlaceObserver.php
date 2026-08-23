<?php

namespace App\Observers;

use App\Models\Place;
use App\Services\AiRecommendationService;

class PlaceObserver
{
    public function __construct(private AiRecommendationService $aiRecommendationService)
    {
    }

    public function saved(Place $place): void
    {
        $this->sync();
    }

    public function deleted(Place $place): void
    {
        $this->sync();
    }

    private function sync(): void
    {
        $this->aiRecommendationService->notifyPlacesChanged();
    }
}
