<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Services\AiRecommendationService;
use Illuminate\Http\Request;

class AiRecommendationController extends Controller
{
    public function __construct(private readonly AiRecommendationService $aiRecommendationService)
    {
    }

    public function nearbyRecommendations(Request $request)
    {
        $payload = $request->validate($this->baseRules() + [
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return api_success(
            $this->aiRecommendationService->nearbyRecommendations($payload),
            'AI nearby recommendations'
        );
    }

    public function smartTripPlanner(Request $request)
    {
        $payload = $request->validate($this->baseRules() + [
            'days' => ['required', 'integer', 'min:1', 'max:14'],
        ]);

        return api_success(
            $this->aiRecommendationService->smartTripPlanner($payload),
            'AI smart trip plan'
        );
    }

    private function baseRules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['required', 'string'],
            'budget' => ['required', 'numeric', 'min:0'],
            'season' => ['required', 'string', 'in:winter,spring,summer,autumn'],
            'weather' => ['required', 'string', 'in:sunny,cloudy,rainy,hot,cold'],
            'preferred_time' => ['required', 'string', 'in:morning,afternoon,evening,sunset'],
            'preferred_activity_level' => ['required', 'integer', 'min:1', 'max:4'],
            'pace' => ['required', 'string', 'in:slow,relaxed,medium,balanced,intensive,active'],
        ];
    }
}
