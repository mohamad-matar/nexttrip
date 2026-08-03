#!/usr/bin/env python3
"""Generate a user-place recommendation training dataset.

Each output row represents one recommendation decision:
user profile + candidate place features -> recommended label.
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import math
import random
from pathlib import Path
from typing import Any


INTERESTS = [
    "food",
    "nature",
    "historic",
    "culture",
    "shopping",
    "sport",
    "family",
    "religious",
    "attraction",
    "accommodation",
    "general",
]

SEASONS = ["winter", "spring", "summer", "autumn"]
TIMES = ["morning", "afternoon", "evening", "sunset"]
WEATHER = ["sunny", "cloudy", "rainy", "hot", "cold"]
PACES = ["relaxed", "balanced", "active"]


def stable_seed(value: str) -> int:
    digest = hashlib.sha256(value.encode("utf-8")).hexdigest()
    return int(digest[:16], 16)


def parse_float(value: str, default: float = 0.0) -> float:
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def parse_int(value: str, default: int = 0) -> int:
    try:
        return int(float(value))
    except (TypeError, ValueError):
        return default


def haversine_km(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    radius = 6371.0
    phi1 = math.radians(lat1)
    phi2 = math.radians(lat2)
    d_phi = math.radians(lat2 - lat1)
    d_lambda = math.radians(lon2 - lon1)

    a = (
        math.sin(d_phi / 2) ** 2
        + math.cos(phi1) * math.cos(phi2) * math.sin(d_lambda / 2) ** 2
    )
    return radius * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))


def read_places(path: Path) -> list[dict[str, Any]]:
    with path.open(encoding="utf-8", newline="") as handle:
        rows = list(csv.DictReader(handle))

    places = []
    for row in rows:
        places.append(
            {
                **row,
                "latitude": parse_float(row.get("latitude", "")),
                "longitude": parse_float(row.get("longitude", "")),
                "cost": parse_int(row.get("cost", "")),
                "duration": parse_int(row.get("duration", "")),
                "activity_level": parse_int(row.get("activity_level", "")),
                "is_outdoor": parse_int(row.get("is_outdoor", "")),
                "average_rating": parse_float(row.get("average_rating", "")),
                "reviews_count": parse_int(row.get("reviews_count", "")),
            }
        )
    return places


def build_users(places: list[dict[str, Any]], count: int, rng: random.Random) -> list[dict[str, Any]]:
    users = []
    for user_id in range(1, count + 1):
        home_place = rng.choice(places)
        primary_interest = rng.choice(INTERESTS)
        secondary_interest = rng.choice([item for item in INTERESTS if item != primary_interest])
        users.append(
            {
                "user_id": user_id,
                "home_latitude": home_place["latitude"] + rng.uniform(-0.06, 0.06),
                "home_longitude": home_place["longitude"] + rng.uniform(-0.06, 0.06),
                "primary_interest": primary_interest,
                "secondary_interest": secondary_interest,
                "budget": rng.choice([5, 10, 15, 25, 40, 70, 120]),
                "preferred_activity_level": rng.randint(1, 4),
                "season": rng.choice(SEASONS),
                "weather": rng.choice(WEATHER),
                "preferred_time": rng.choice(TIMES),
                "pace": rng.choice(PACES),
            }
        )
    return users


def season_match(user_season: str, best_season: str) -> int:
    if best_season == "all_year":
        return 1
    if best_season == user_season:
        return 1
    if best_season == "spring_autumn" and user_season in {"spring", "autumn"}:
        return 1
    return 0


def weather_match(weather: str, is_outdoor: int) -> int:
    if not is_outdoor:
        return 1
    return int(weather in {"sunny", "cloudy"})


def pace_match(pace: str, duration: int, activity_level: int) -> int:
    if pace == "relaxed":
        return int(duration <= 120 and activity_level <= 2)
    if pace == "active":
        return int(activity_level >= 2)
    return int(duration <= 180)


def recommendation_score(user: dict[str, Any], place: dict[str, Any]) -> tuple[float, dict[str, Any]]:
    distance = haversine_km(
        user["home_latitude"],
        user["home_longitude"],
        place["latitude"],
        place["longitude"],
    )
    interest = int(
        place["category"] in {user["primary_interest"], user["secondary_interest"]}
    )
    budget = int(place["cost"] <= user["budget"])
    season = season_match(user["season"], place.get("best_season", "all_year"))
    weather = weather_match(user["weather"], place["is_outdoor"])
    time = int(
        place.get("recommended_time", "afternoon") == user["preferred_time"]
        or place.get("recommended_time", "afternoon") == "afternoon"
    )
    activity_gap = abs(place["activity_level"] - user["preferred_activity_level"])
    activity = max(0, 1 - activity_gap / 3)
    pace = pace_match(user["pace"], place["duration"], place["activity_level"])
    rating_norm = place["average_rating"] / 5
    reviews_norm = min(1.0, math.log1p(place["reviews_count"]) / math.log1p(500))
    distance_norm = max(0.0, 1 - min(distance, 80) / 80)

    score = (
        0.24 * interest
        + 0.15 * distance_norm
        + 0.13 * budget
        + 0.11 * weather
        + 0.10 * season
        + 0.08 * rating_norm
        + 0.06 * reviews_norm
        + 0.05 * activity
        + 0.04 * pace
        + 0.04 * time
    )

    features = {
        "interest_match": interest,
        "distance": round(distance, 2),
        "budget_match": budget,
        "weather_match": weather,
        "season_match": season,
        "time_match": time,
        "rating": place["average_rating"],
        "reviews": place["reviews_count"],
        "activity_level": place["activity_level"],
        "activity_match": round(activity, 3),
        "is_outdoor": place["is_outdoor"],
        "pace_match": pace,
        "cost": place["cost"],
        "duration": place["duration"],
    }
    return score, features


def generate_rows(
    places: list[dict[str, Any]],
    users: list[dict[str, Any]],
    target_rows: int,
    seed: int,
) -> list[dict[str, Any]]:
    rng = random.Random(seed)
    rows = []

    while len(rows) < target_rows:
        user = rng.choice(users)
        place = rng.choice(places)
        score, features = recommendation_score(user, place)
        local_rng = random.Random(stable_seed(f"{user['user_id']}:{place['place_id']}:{seed}"))
        noisy_score = min(1.0, max(0.0, score + local_rng.uniform(-0.06, 0.06)))
        recommended = int(noisy_score >= 0.58)

        rows.append(
            {
                "user_id": user["user_id"],
                "place_id": place["place_id"],
                "user_primary_interest": user["primary_interest"],
                "user_secondary_interest": user["secondary_interest"],
                "user_budget": user["budget"],
                "user_season": user["season"],
                "user_weather": user["weather"],
                "user_preferred_time": user["preferred_time"],
                "user_pace": user["pace"],
                **features,
                "recommendation_score_rule": round(noisy_score, 4),
                "recommended": recommended,
            }
        )

    return rows


def write_csv(rows: list[dict[str, Any]], output: Path) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)
    fieldnames = [
        "user_id",
        "place_id",
        "user_primary_interest",
        "user_secondary_interest",
        "user_budget",
        "user_season",
        "user_weather",
        "user_preferred_time",
        "user_pace",
        "interest_match",
        "distance",
        "budget_match",
        "weather_match",
        "season_match",
        "time_match",
        "rating",
        "reviews",
        "activity_level",
        "activity_match",
        "is_outdoor",
        "pace_match",
        "cost",
        "duration",
        "recommendation_score_rule",
        "recommended",
    ]
    with output.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Generate recommendation training rows.")
    parser.add_argument("--places", default="data/places.csv", help="Input places CSV")
    parser.add_argument("--output", default="data/training_dataset.csv", help="Output training CSV")
    parser.add_argument("--rows", type=int, default=100000, help="Number of training rows")
    parser.add_argument("--users", type=int, default=1000, help="Number of synthetic users")
    parser.add_argument("--seed", type=int, default=42, help="Random seed")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    rng = random.Random(args.seed)
    places = read_places(Path(args.places))
    if not places:
        raise RuntimeError(f"No places found in {args.places}")

    users = build_users(places, args.users, rng)
    rows = generate_rows(places, users, args.rows, args.seed)
    write_csv(rows, Path(args.output))

    positives = sum(row["recommended"] for row in rows)
    print(f"Wrote {len(rows)} rows to {args.output}")
    print(f"Positive labels: {positives} ({positives / len(rows):.1%})")
    print(f"Negative labels: {len(rows) - positives} ({(len(rows) - positives) / len(rows):.1%})")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
