<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\JsonResponse;

class AiPlaceController extends Controller
{
    private const CATEGORY_BY_ID = [
        1 => 'nature',
        2 => 'food',
        3 => 'food',
        4 => 'accommodation',
        5 => 'accommodation',
        6 => 'accommodation',
        7 => 'accommodation',
        8 => 'nature',
        9 => 'nature',
        10 => 'nature',
        11 => 'nature',
        12 => 'nature',
        13 => 'culture',
        14 => 'culture',
        15 => 'culture',
        16 => 'culture',
        17 => 'shopping',
        18 => 'shopping',
        19 => 'shopping',
        20 => 'family',
        21 => 'family',
        22 => 'religious',
        23 => 'religious',
        24 => 'religious',
        25 => 'historic',
        26 => 'historic',
        27 => 'historic',
    ];

    private const INTEREST_BY_ID = [
        1 => 'historic',
        2 => 'nature',
        3 => 'nature',
        4 => 'culture',
        5 => 'food',
        6 => 'shopping',
        7 => 'religious',
        8 => 'family',
        9 => 'nature',
        10 => 'sport',
        11 => 'culture',
    ];

    private const ACTIVITY_LEVEL = [
        'relax' => 1,
        'sensible' => 2,
        'vigour' => 3,
    ];

    public function index(): JsonResponse
    {
        $places = Place::query()
            ->with(['images' => fn ($query) => $query->orderBy('order'), 'interests'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')
            ->get()
            ->map(fn (Place $place) => $this->toAiPlace($place))
            ->values();

        return response()->json($places);
    }

    private function toAiPlace(Place $place): array
    {
        $bestSeasons = $this->listValue($place->best_seasons);
        $recommendedTimes = $this->listValue($place->recommended_times);

        return [
            'database_place_id' => $place->id,
            'place_id' => 'db/' . $place->id,
            'osm_place_id' => null,
            'name' => $place->name,
            'category' => self::CATEGORY_BY_ID[$place->category_id] ?? 'general',
            'interests' => $place->interests
                ->map(fn ($interest) => self::INTEREST_BY_ID[$interest->id] ?? null)
                ->filter()
                ->values()
                ->all(),
            'latitude' => (float) $place->latitude,
            'longitude' => (float) $place->longitude,
            'cost' => (int) $place->cost,
            'duration' => (int) ($place->expected_duration_minutes ?: 60),
            'activity_level' => self::ACTIVITY_LEVEL[$place->activity_level] ?? 2,
            'is_outdoor' => (int) (bool) $place->is_outdoor,
            'average_rating' => (float) $place->average_rating,
            'reviews_count' => (int) $place->reviews_count,
            'best_season' => $bestSeasons[0] ?? 'all_year',
            'recommended_time' => $recommendedTimes[0] ?? 'afternoon',
            'opening_hours' => $this->openingHours($place->opening_hours),
            'image_urls' => $place->images
                ->map(fn ($image) => $image->image_url_full)
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function listValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }

        return [];
    }

    private function openingHours(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        if (is_array($value)) {
            return (string) ($value['default'] ?? json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return (string) ($decoded['default'] ?? json_encode($decoded, JSON_UNESCAPED_UNICODE));
        }

        return (string) $value;
    }
}
