<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\TripPlace;
use App\Models\User;
use App\Services\AiRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiRecommendationController extends Controller
{
    public function __construct(private readonly AiRecommendationService $aiRecommendationService) {}

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
            'start_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $plan = $this->aiRecommendationService->smartTripPlanner($payload);

        // حفظ الخطة في حال كان منشئ الخطة سائح وليس ضيف
        $user = Auth::user();
        
        $trip = ($user instanceof User && $user->isTourist())
            ? $this->storeAiTrip( $payload, $plan)
            : null;

        return api_success([
            ...$plan,
            'trip' => $trip ? new TripResource($trip->load('tripPlaces.place')) : null,
            'trip_id' => $trip?->id,
        ], 'AI smart trip plan');
    }

    private function storeAiTrip( array $payload, array $plan): Trip
    {
        $tourist_id  = Auth::id();
        $summary = $plan['summary'] ?? [];
        $planDays = $plan['days'] ?? [];

        $startDate = $summary['start_date'] ?? $payload['start_date'] ?? now()->toDateString();
        $days = $summary['days'] ?? $payload['days'] ?? 1;
        $totalCost = $summary['total_cost'] ?? 0;

        $trip = Trip::create([
            'user_id' => $tourist_id,
            
            'title' => !empty($payload['title']) ? $payload['title'] : 'خطة سفر ذكية - '.$startDate,
            'start_date' => $startDate,
            'day_count' => $days,
            'budget_max' => $payload['budget'] ?? null,
            'trip_pace' => $this->mapPace($payload['pace'] ?? 'medium'),
            'preferred_activity_level' => $this->mapActivityLevel($payload['preferred_activity_level'] ?? 2),
            'total_estimated_cost' => $totalCost,
            'source' => 'ai',
            'ai_payload' => $payload,
        ]);

        foreach ($planDays as $day) {
            $dayNumber = $day['day'] ?? 1;

            foreach ($day['activities'] ?? [] as $index => $activity) {
                TripPlace::create([
                    'trip_id' => $trip->id,
                    'place_id' => $activity['database_place_id'] ?? null,
                    'day_number' => $dayNumber,
                    'order' => $index + 1,
                    'start_time' => $activity['start_time'] ?? '09:00',
                    'duration_minutes' => (int) ($activity['duration'] ?? 60),
                    'travel_minutes' => (int) ($activity['travel_time_from_previous'] ?? 0),
                    'estimated_cost' => $activity['cost'] ?? 0,
                    'note' => $activity['category'] ?? null,
                ]);
            }
        }

        return $trip;
    }

    private function mapPace(string $pace): string
    {
        return match ($pace) {
            'slow', 'relaxed' => 'slow',
            'intensive', 'active' => 'intensive',
            default => 'medium',
        };
    }

    private function mapActivityLevel(int $level): string
    {
        return match (true) {
            $level <= 1 => 'relax',
            $level >= 3 => 'vigour',
            default => 'sensible',
        };
    }

    private function baseRules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:191'],
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
