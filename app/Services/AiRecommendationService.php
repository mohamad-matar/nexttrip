<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AiRecommendationService
{
    public function nearbyRecommendations(array $payload): array
    {
        return $this->post('/api/ai/nearby-recommendations', $payload);
    }

    public function smartTripPlanner(array $payload): array
    {
        return $this->post('/api/ai/smart-trip-planner', $payload);
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
            abort(
                $exception->response?->status() ?: 502,
                $exception->response?->json('detail') ?: 'AI service request failed.'
            );
        }
    }
}
