#!/usr/bin/env python3
"""Score candidate places with the trained recommendation model."""

from __future__ import annotations

import argparse
import csv
import json
from pathlib import Path
from typing import Any

import joblib
import pandas as pd

try:
    from scripts.generate_training_dataset import (
        haversine_km,
        parse_float,
        parse_int,
        pace_match,
        season_match,
        weather_match,
    )
except ModuleNotFoundError:
    from generate_training_dataset import (
        haversine_km,
        parse_float,
        parse_int,
        pace_match,
        season_match,
        weather_match,
    )


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


def build_features(user: dict[str, Any], place: dict[str, Any]) -> dict[str, Any]:
    distance = haversine_km(
        user["latitude"],
        user["longitude"],
        place["latitude"],
        place["longitude"],
    )
    user_interests = set(user["interests"])
    place_interests = set(place.get("interests", []))
    interest_match = int(place["category"] in user_interests or bool(place_interests & user_interests))
    budget_match = int(place["cost"] <= user["budget"])
    place_season_match = season_match(user["season"], place.get("best_season", "all_year"))
    place_weather_match = weather_match(user["weather"], place["is_outdoor"])
    time_match = int(
        place.get("recommended_time", "afternoon") == user["preferred_time"]
        or place.get("recommended_time", "afternoon") == "afternoon"
    )
    activity_gap = abs(place["activity_level"] - user["preferred_activity_level"])
    activity_match = max(0, 1 - activity_gap / 3)

    return {
        "interest_match": interest_match,
        "distance": round(distance, 2),
        "budget_match": budget_match,
        "weather_match": place_weather_match,
        "season_match": place_season_match,
        "time_match": time_match,
        "rating": place["average_rating"],
        "reviews": place["reviews_count"],
        "activity_level": place["activity_level"],
        "activity_match": round(activity_match, 3),
        "is_outdoor": place["is_outdoor"],
        "pace_match": pace_match(user["pace"], place["duration"], place["activity_level"]),
        "cost": place["cost"],
        "duration": place["duration"],
    }


def score_places(
    model_artifact: dict[str, Any],
    places: list[dict[str, Any]],
    user: dict[str, Any],
    limit: int,
) -> list[dict[str, Any]]:
    feature_columns = model_artifact["feature_columns"]
    features = [build_features(user, place) for place in places]
    frame = pd.DataFrame(features, columns=feature_columns)
    probabilities = model_artifact["model"].predict_proba(frame)[:, 1]

    scored = []
    for place, probability in zip(places, probabilities):
        scored.append(
            {
                "database_place_id": place.get("database_place_id"),
                "place_id": place["place_id"],
                "osm_place_id": place.get("osm_place_id") or place["place_id"],
                "name": place["name"],
                "category": place["category"],
                "score": round(float(probability), 4),
                "image_urls": place.get("image_urls", []),
            }
        )

    scored.sort(key=lambda item: item["score"], reverse=True)
    return scored[:limit]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Predict nearby place recommendations.")
    parser.add_argument("--model", default="models/recommender_random_forest.joblib")
    parser.add_argument("--places", default="data/places.csv")
    parser.add_argument("--latitude", type=float, required=True)
    parser.add_argument("--longitude", type=float, required=True)
    parser.add_argument("--interests", default="historic,nature")
    parser.add_argument("--budget", type=int, default=25)
    parser.add_argument("--season", default="spring")
    parser.add_argument("--weather", default="sunny")
    parser.add_argument("--preferred-time", default="morning")
    parser.add_argument("--preferred-activity-level", type=int, default=2)
    parser.add_argument("--pace", default="balanced")
    parser.add_argument("--limit", type=int, default=10)
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
    recommendations = score_places(model_artifact, places, user, args.limit)
    print(json.dumps(recommendations, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
