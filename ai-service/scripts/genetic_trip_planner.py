#!/usr/bin/env python3
"""Build smart trip plans with a Genetic Algorithm over ML-scored places."""

from __future__ import annotations

import argparse
from datetime import date, timedelta
import json
import math
import random
import re
from dataclasses import dataclass
from pathlib import Path
from typing import Any

import joblib
import pandas as pd

try:
    from scripts.generate_training_dataset import haversine_km
    from scripts.predict_recommendations import build_features, read_places
except ModuleNotFoundError:
    from generate_training_dataset import haversine_km
    from predict_recommendations import build_features, read_places


PACE_CONFIG = {
    "slow": {"activities_per_day": 3, "activity_level": 1},
    "relaxed": {"activities_per_day": 3, "activity_level": 1},
    "medium": {"activities_per_day": 4, "activity_level": 2},
    "balanced": {"activities_per_day": 4, "activity_level": 2},
    "intensive": {"activities_per_day": 5, "activity_level": 3},
    "active": {"activities_per_day": 5, "activity_level": 3},
}

DAY_START_MINUTE = 9 * 60
DAY_END_MINUTE = 20 * 60
AVERAGE_CITY_SPEED_KMH = 28
DEFAULT_MAX_CANDIDATES = 120
DEFAULT_POPULATION_SIZE = 80
DEFAULT_GENERATIONS = 120
DEFAULT_MUTATION_RATE = 0.18
DEFAULT_SEED = 42


@dataclass(frozen=True)
class Candidate:
    place: dict[str, Any]
    ml_score: float


def normalize_pace(pace: str) -> str:
    return pace if pace in PACE_CONFIG else "balanced"


def minutes_to_time(minutes: int) -> str:
    hours = minutes // 60
    mins = minutes % 60
    return f"{hours:02d}:{mins:02d}"


def travel_minutes(distance_km: float) -> int:
    return max(5, int(math.ceil(distance_km / AVERAGE_CITY_SPEED_KMH * 60)))


def score_candidates(
    model_artifact: dict[str, Any],
    places: list[dict[str, Any]],
    user: dict[str, Any],
    max_candidates: int,
) -> list[Candidate]:
    feature_columns = model_artifact["feature_columns"]
    features = [build_features(user, place) for place in places]
    frame = pd.DataFrame(features, columns=feature_columns)
    probabilities = model_artifact["model"].predict_proba(frame)[:, 1]

    candidates = [
        Candidate(place=place, ml_score=float(probability))
        for place, probability in zip(places, probabilities)
    ]
    candidates.sort(key=lambda candidate: candidate.ml_score, reverse=True)
    return candidates[:max_candidates]


def parse_opening_window(opening_hours: str) -> tuple[int, int] | None:
    if not opening_hours:
        return None
    match = re.search(r"(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})", opening_hours)
    if not match:
        return None
    start_hour, start_minute, end_hour, end_minute = [int(part) for part in match.groups()]
    return start_hour * 60 + start_minute, end_hour * 60 + end_minute


def is_open_at(place: dict[str, Any], minute: int) -> bool:
    window = parse_opening_window(str(place.get("opening_hours", "")))
    if not window:
        return True
    start, end = window
    return start <= minute <= end


def preferred_time_bonus(place: dict[str, Any], start_minute: int) -> float:
    recommended_time = place.get("recommended_time", "afternoon")
    windows = {
        "morning": (8 * 60, 12 * 60),
        "afternoon": (12 * 60, 17 * 60),
        "evening": (17 * 60, 21 * 60),
        "sunset": (17 * 60, 19 * 60),
    }
    start, end = windows.get(recommended_time, windows["afternoon"])
    return 1.0 if start <= start_minute <= end else 0.35


def create_chromosome(
    candidates: list[Candidate],
    length: int,
    rng: random.Random,
) -> list[int]:
    length = min(length, len(candidates))
    weighted_indexes = list(range(len(candidates)))
    weights = [max(0.01, candidate.ml_score) for candidate in candidates]
    chromosome = []
    seen = set()
    while len(chromosome) < length:
        index = rng.choices(weighted_indexes, weights=weights, k=1)[0]
        if index not in seen:
            chromosome.append(index)
            seen.add(index)
    return chromosome


def split_by_day(chromosome: list[int], days: int, activities_per_day: int) -> list[list[int]]:
    return [
        chromosome[index * activities_per_day : (index + 1) * activities_per_day]
        for index in range(days)
    ]


def chromosome_cost(chromosome: list[int], candidates: list[Candidate]) -> int:
    return sum(candidates[index].place["cost"] for index in chromosome)


def evaluate_fitness(
    chromosome: list[int],
    candidates: list[Candidate],
    user: dict[str, Any],
    days: int,
    budget: int,
    activities_per_day: int,
) -> float:
    if not chromosome:
        return -9999

    unique_count = len(set(chromosome))
    duplicate_penalty = (len(chromosome) - unique_count) * 3.0
    total_cost = chromosome_cost(chromosome, candidates)
    budget_penalty = max(0, total_cost - budget) / max(1, budget) * 6.0

    score = 0.0
    total_travel_minutes = 0
    missed_time_windows = 0
    closed_visits = 0
    activity_penalty = 0.0

    target_activity = PACE_CONFIG[normalize_pace(user["pace"])]["activity_level"]
    for day_indexes in split_by_day(chromosome, days, activities_per_day):
        current_lat = user["latitude"]
        current_lon = user["longitude"]
        current_minute = DAY_START_MINUTE

        for index in day_indexes:
            candidate = candidates[index]
            place = candidate.place
            distance = haversine_km(current_lat, current_lon, place["latitude"], place["longitude"])
            commute = travel_minutes(distance)
            current_minute += commute
            total_travel_minutes += commute

            if current_minute + place["duration"] > DAY_END_MINUTE:
                score -= 3.0

            if not is_open_at(place, current_minute):
                closed_visits += 1

            time_bonus = preferred_time_bonus(place, current_minute)
            if time_bonus < 1:
                missed_time_windows += 1

            activity_penalty += abs(place["activity_level"] - target_activity) * 0.18
            score += candidate.ml_score * 3.0
            score += time_bonus * 0.35
            score += min(1.0, place["average_rating"] / 5) * 0.4

            current_minute += place["duration"]
            current_lat = place["latitude"]
            current_lon = place["longitude"]

    travel_penalty = total_travel_minutes / 180
    return score - duplicate_penalty - budget_penalty - travel_penalty - activity_penalty - closed_visits - missed_time_windows * 0.15


def tournament_selection(
    population: list[list[int]],
    fitness_scores: list[float],
    rng: random.Random,
    size: int = 4,
) -> list[int]:
    selected_indexes = rng.sample(range(len(population)), min(size, len(population)))
    best_index = max(selected_indexes, key=lambda index: fitness_scores[index])
    return population[best_index][:]


def ordered_crossover(parent_a: list[int], parent_b: list[int], rng: random.Random) -> list[int]:
    if len(parent_a) < 2:
        return parent_a[:]

    start, end = sorted(rng.sample(range(len(parent_a)), 2))
    child = [-1] * len(parent_a)
    child[start:end] = parent_a[start:end]
    used = set(child[start:end])
    fill_values = [item for item in parent_b if item not in used]

    fill_index = 0
    for index, value in enumerate(child):
        if value == -1:
            child[index] = fill_values[fill_index]
            fill_index += 1
    return child


def mutate(
    chromosome: list[int],
    candidates_count: int,
    rng: random.Random,
    mutation_rate: float,
) -> list[int]:
    mutated = chromosome[:]
    if rng.random() < mutation_rate and len(mutated) >= 2:
        first, second = rng.sample(range(len(mutated)), 2)
        mutated[first], mutated[second] = mutated[second], mutated[first]

    if rng.random() < mutation_rate:
        replace_at = rng.randrange(len(mutated))
        used = set(mutated)
        available = [index for index in range(candidates_count) if index not in used]
        if available:
            mutated[replace_at] = rng.choice(available)
    return mutated


def run_genetic_algorithm(
    candidates: list[Candidate],
    user: dict[str, Any],
    days: int,
    budget: int,
    activities_per_day: int,
    population_size: int,
    generations: int,
    mutation_rate: float,
    seed: int,
) -> tuple[list[int], float]:
    rng = random.Random(seed)
    chromosome_length = min(days * activities_per_day, len(candidates))
    population = [
        create_chromosome(candidates, chromosome_length, rng)
        for _ in range(population_size)
    ]

    best = population[0]
    best_fitness = float("-inf")

    for _ in range(generations):
        fitness_scores = [
            evaluate_fitness(chromosome, candidates, user, days, budget, activities_per_day)
            for chromosome in population
        ]
        generation_best_index = max(range(len(population)), key=lambda index: fitness_scores[index])
        if fitness_scores[generation_best_index] > best_fitness:
            best = population[generation_best_index][:]
            best_fitness = fitness_scores[generation_best_index]

        next_population = [best[:]]
        while len(next_population) < population_size:
            parent_a = tournament_selection(population, fitness_scores, rng)
            parent_b = tournament_selection(population, fitness_scores, rng)
            child = ordered_crossover(parent_a, parent_b, rng)
            child = mutate(child, len(candidates), rng, mutation_rate)
            next_population.append(child)
        population = next_population

    return best, best_fitness


def build_itinerary(
    chromosome: list[int],
    candidates: list[Candidate],
    user: dict[str, Any],
    days: int,
    activities_per_day: int,
    start_date: str | None = None,
) -> list[dict[str, Any]]:
    itinerary = []
    start = date.fromisoformat(start_date) if start_date else None
    for day_number, day_indexes in enumerate(split_by_day(chromosome, days, activities_per_day), start=1):
        current_lat = user["latitude"]
        current_lon = user["longitude"]
        current_minute = DAY_START_MINUTE
        activities = []

        for index in day_indexes:
            candidate = candidates[index]
            place = candidate.place
            distance = haversine_km(current_lat, current_lon, place["latitude"], place["longitude"])
            commute = travel_minutes(distance)
            current_minute += commute
            start_minute = current_minute
            end_minute = min(DAY_END_MINUTE, start_minute + place["duration"])

            activities.append(
                {
                    "database_place_id": place.get("database_place_id"),
                    "place_id": place["place_id"],
                    "osm_place_id": place.get("osm_place_id") or place["place_id"],
                    "name": place["name"],
                    "category": place["category"],
                    "latitude": place["latitude"],
                    "longitude": place["longitude"],
                    "image_urls": place.get("image_urls", []),
                    "score": round(candidate.ml_score, 4),
                    "cost": place["cost"],
                    "duration": place["duration"],
                    "activity_level": place["activity_level"],
                    "is_outdoor": place["is_outdoor"],
                    "opening_hours": place.get("opening_hours", ""),
                    "recommended_time": place.get("recommended_time", ""),
                    "start_time": minutes_to_time(start_minute),
                    "end_time": minutes_to_time(end_minute),
                    "travel_time_from_previous": commute,
                    "distance_from_previous": round(distance, 2),
                }
            )

            current_minute = end_minute
            current_lat = place["latitude"]
            current_lon = place["longitude"]

        itinerary.append(
            {
                "day": day_number,
                "date": (start + timedelta(days=day_number - 1)).isoformat() if start else None,
                "total_cost": sum(activity["cost"] for activity in activities),
                "total_duration": sum(activity["duration"] for activity in activities),
                "total_travel_time": sum(activity["travel_time_from_previous"] for activity in activities),
                "activities": activities,
            }
        )
    return itinerary


def plan_trip(
    model_artifact: dict[str, Any],
    places: list[dict[str, Any]],
    user: dict[str, Any],
    days: int,
    budget: int,
    start_date: str | None = None,
    max_candidates: int = DEFAULT_MAX_CANDIDATES,
    population_size: int = DEFAULT_POPULATION_SIZE,
    generations: int = DEFAULT_GENERATIONS,
    mutation_rate: float = DEFAULT_MUTATION_RATE,
    seed: int = DEFAULT_SEED,
) -> dict[str, Any]:
    pace = normalize_pace(user["pace"])
    user = {**user, "pace": pace}
    activities_per_day = PACE_CONFIG[pace]["activities_per_day"]
    candidates = score_candidates(model_artifact, places, user, max_candidates)

    best_chromosome, best_fitness = run_genetic_algorithm(
        candidates=candidates,
        user=user,
        days=days,
        budget=budget,
        activities_per_day=activities_per_day,
        population_size=population_size,
        generations=generations,
        mutation_rate=mutation_rate,
        seed=seed,
    )
    itinerary = build_itinerary(best_chromosome, candidates, user, days, activities_per_day, start_date)
    selected_ids = [activity["place_id"] for day in itinerary for activity in day["activities"]]

    return {
        "summary": {
            "algorithm": "machine_learning_scores_with_genetic_algorithm",
            "start_date": start_date,
            "end_date": (date.fromisoformat(start_date) + timedelta(days=days - 1)).isoformat() if start_date else None,
            "days": days,
            "pace": pace,
            "budget": budget,
            "total_places": len(selected_ids),
            "total_cost": sum(day["total_cost"] for day in itinerary),
            "total_duration": sum(day["total_duration"] for day in itinerary),
            "total_travel_time": sum(day["total_travel_time"] for day in itinerary),
            "fitness": round(best_fitness, 4),
            "generations": generations,
            "population_size": population_size,
        },
        "days": itinerary,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Build a trip plan with ML scores and GA.")
    parser.add_argument("--model", default="models/recommender_random_forest.joblib")
    parser.add_argument("--places", default="data/places.csv")
    parser.add_argument("--latitude", type=float, required=True)
    parser.add_argument("--longitude", type=float, required=True)
    parser.add_argument("--interests", default="historic,nature")
    parser.add_argument("--budget", type=int, default=120)
    parser.add_argument("--start-date")
    parser.add_argument("--days", type=int, default=3)
    parser.add_argument("--season", default="spring")
    parser.add_argument("--weather", default="sunny")
    parser.add_argument("--preferred-time", default="morning")
    parser.add_argument("--preferred-activity-level", type=int, default=2)
    parser.add_argument("--pace", default="balanced")
    parser.add_argument("--seed", type=int, default=42)
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    model_artifact = joblib.load(args.model)
    places = read_places(Path(args.places))
    user = {
        "latitude": args.latitude,
        "longitude": args.longitude,
        "interests": [item.strip() for item in args.interests.split(",") if item.strip()],
        "budget": args.budget,
        "season": args.season,
        "weather": args.weather,
        "preferred_time": args.preferred_time,
        "preferred_activity_level": args.preferred_activity_level,
        "pace": args.pace,
    }
    result = plan_trip(model_artifact, places, user, args.days, args.budget, start_date=args.start_date, seed=args.seed)
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
