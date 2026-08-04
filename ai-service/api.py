from __future__ import annotations

from datetime import date
import os
from pathlib import Path
from typing import Any

import joblib
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from scripts.genetic_trip_planner import plan_trip
from scripts.backend_places import read_backend_places
from scripts.predict_recommendations import read_places, score_places


BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "models/recommender_random_forest.joblib"
PLACES_PATH = BASE_DIR / "data/places.csv"
PLACES_SOURCE = os.getenv("AI_PLACES_SOURCE", "api").lower()

app = FastAPI(title="Place Recommendation API")

model_artifact: dict[str, Any] | None = None
places: list[dict[str, Any]] = []
places_source: str = PLACES_SOURCE
places_error: str | None = None


class RecommendationRequest(BaseModel):
    latitude: float
    longitude: float
    interests: list[str] = Field(default_factory=lambda: ["historic", "nature"])
    budget: int = 25
    season: str = "spring"
    weather: str = "sunny"
    preferred_time: str = "morning"
    preferred_activity_level: int = 2
    pace: str = "balanced"
    limit: int = Field(default=10, ge=1, le=100)


class TripPlannerRequest(RecommendationRequest):
    start_date: date
    days: int = Field(default=3, ge=1, le=14)


@app.on_event("startup")
def load_artifacts() -> None:
    global model_artifact, places
    model_artifact = joblib.load(MODEL_PATH)
    refresh_places(raise_on_error=False)


def load_places() -> list[dict[str, Any]]:
    if PLACES_SOURCE == "csv":
        return read_places(PLACES_PATH)
    return read_backend_places()


def refresh_places(raise_on_error: bool = True) -> None:
    global places, places_error
    try:
        places = load_places()
        places_error = None
    except Exception as exc:
        places_error = str(exc)
        if raise_on_error:
            raise HTTPException(
                status_code=503,
                detail=f"Places API is not available: {places_error}",
            ) from exc


@app.get("/health")
def health() -> dict[str, Any]:
    return {
        "status": "ok",
        "model_loaded": model_artifact is not None,
        "places_source": places_source,
        "places_count": len(places),
        "places_error": places_error,
    }


@app.post("/admin/reload-places")
def reload_places() -> dict[str, Any]:
    refresh_places()
    return {
        "status": "ok",
        "places_source": places_source,
        "places_count": len(places),
    }


@app.post("/api/ai/nearby-recommendations")
def nearby_recommendations(payload: RecommendationRequest) -> list[dict[str, Any]]:
    if model_artifact is None:
        raise RuntimeError("Model is not loaded")
    refresh_places(raise_on_error=True)

    user = {
        "latitude": payload.latitude,
        "longitude": payload.longitude,
        "interests": payload.interests,
        "budget": payload.budget,
        "season": payload.season,
        "weather": payload.weather,
        "preferred_time": payload.preferred_time,
        "preferred_activity_level": payload.preferred_activity_level,
        "pace": payload.pace,
    }
    return score_places(model_artifact, places, user, payload.limit)


@app.post("/api/ai/smart-trip-planner")
def smart_trip_planner(payload: TripPlannerRequest) -> dict[str, Any]:
    if model_artifact is None:
        raise RuntimeError("Model is not loaded")
    refresh_places(raise_on_error=True)

    user = {
        "latitude": payload.latitude,
        "longitude": payload.longitude,
        "interests": payload.interests,
        "budget": payload.budget,
        "season": payload.season,
        "weather": payload.weather,
        "preferred_time": payload.preferred_time,
        "preferred_activity_level": payload.preferred_activity_level,
        "pace": payload.pace,
    }
    return plan_trip(
        model_artifact=model_artifact,
        places=places,
        user=user,
        days=payload.days,
        budget=payload.budget,
        start_date=payload.start_date.isoformat(),
    )
