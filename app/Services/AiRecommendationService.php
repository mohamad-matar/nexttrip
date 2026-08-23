<?php

namespace App\Services;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\TripPlace;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRecommendationService
{   
    public function nearbyRecommendations(array $payload): array
    {
        return $this->post('/api/ai/nearby-recommendations', $payload);
    }

    public function smartTripPlanner(array $payload , bool $save): array
    {
        $plan =  $this->post('/api/ai/smart-trip-planner', $payload);
        $trip = $save? $this->storeAiTrip( $payload, $plan) : null;
        return [
            ...$plan ,
            'trip' => $trip ? new TripResource($trip->load('tripPlaces.place')) : null,
            'trip_id' => $trip?->id,
        ];
    }

    public function notifyPlacesChanged(): void
    {
        try {
            Http::timeout(5)
                ->acceptJson()
                ->post(rtrim(config('ai.base_url'), '/') . '/admin/reload-places')
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('AI places reload failed: ' . $exception->getMessage());
        }
    }

    private function post(string $endpoint, array $payload): array
    {
        try {
            $response = Http::timeout(config('ai.timeout'))
                ->acceptJson()
                ->asJson()
                ->post(rtrim(config('ai.base_url'), '/') . $endpoint, $payload)
                ->throw();

            return $response->json();
        } catch (ConnectionException) {
            abort(503, 'AI service is not available. Make sure the Python service is running.');
        } catch (RequestException $exception) {
            $detail = $exception->response?->json('detail');
            $message = $detail
                ? "AI service request failed: {$detail}"
                : 'AI service request failed.';

            abort($exception->response?->status() ?: 502, $message);
        }
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
            $order = 0;

            foreach ($day['activities'] ?? [] as $activity) {
                // Only database places can be attached to a saved trip.
                if (empty($activity['database_place_id'])) {
                    continue;
                }

                $order++;

                TripPlace::create([
                    'trip_id' => $trip->id,
                    'place_id' => $activity['database_place_id'],
                    'day_number' => $dayNumber,
                    'order' => $order,
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

}
