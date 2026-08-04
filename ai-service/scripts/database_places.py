#!/usr/bin/env python3
"""Read recommendation places from the Laravel application's database."""

from __future__ import annotations

import json
import os
from decimal import Decimal
from pathlib import Path
from typing import Any

import mysql.connector


DEFAULT_LARAVEL_ENV_PATH = Path(__file__).resolve().parents[2] / ".env"

CATEGORY_BY_ID = {
    1: "nature",
    2: "food",
    3: "food",
    4: "accommodation",
    5: "accommodation",
    6: "accommodation",
    7: "accommodation",
    8: "nature",
    9: "nature",
    10: "nature",
    11: "nature",
    12: "nature",
    13: "culture",
    14: "culture",
    15: "culture",
    16: "culture",
    17: "shopping",
    18: "shopping",
    19: "shopping",
    20: "family",
    21: "family",
    22: "religious",
    23: "religious",
    24: "religious",
    25: "historic",
    26: "historic",
    27: "historic",
}

INTEREST_BY_ID = {
    1: "historic",
    2: "nature",
    3: "nature",
    4: "culture",
    5: "food",
    6: "shopping",
    7: "religious",
    8: "family",
    9: "nature",
    10: "sport",
    11: "culture",
}

ACTIVITY_LEVEL = {
    "relax": 1,
    "sensible": 2,
    "vigour": 3,
}


def read_laravel_env(path: Path = DEFAULT_LARAVEL_ENV_PATH) -> dict[str, str]:
    values = {}
    if not path.exists():
        return values

    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        values[key.strip()] = value.strip().strip('"').strip("'")
    return values


def db_config() -> dict[str, Any]:
    env_path = Path(os.getenv("AI_LARAVEL_ENV_PATH", str(DEFAULT_LARAVEL_ENV_PATH)))
    laravel_env = read_laravel_env(env_path)

    def value(key: str, default: str = "") -> str:
        return os.getenv(key) or laravel_env.get(key, default)

    return {
        "host": value("DB_HOST", "127.0.0.1"),
        "port": int(value("DB_PORT", "3306")),
        "database": value("DB_DATABASE", "next_trip"),
        "user": value("DB_USERNAME", "root"),
        "password": value("DB_PASSWORD", ""),
    }


def parse_json_list(value: Any) -> list[str]:
    if value in (None, ""):
        return []
    if isinstance(value, list):
        return [str(item) for item in value]
    try:
        decoded = json.loads(str(value))
    except json.JSONDecodeError:
        return []
    if isinstance(decoded, list):
        return [str(item) for item in decoded]
    return []


def opening_hours_to_string(value: Any) -> str:
    if value in (None, ""):
        return ""
    if isinstance(value, dict):
        default = value.get("default")
        return str(default) if default else json.dumps(value, ensure_ascii=False)
    try:
        decoded = json.loads(str(value))
    except json.JSONDecodeError:
        return str(value)
    if isinstance(decoded, dict):
        default = decoded.get("default")
        return str(default) if default else json.dumps(decoded, ensure_ascii=False)
    return str(value)


def to_float(value: Any, default: float = 0.0) -> float:
    if value is None:
        return default
    if isinstance(value, Decimal):
        return float(value)
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def image_url(value: str) -> str:
    value = value.strip()
    if value.startswith(("http://", "https://", "/")):
        return value
    return f"/storage/places/{value}"


def normalize_place(row: dict[str, Any]) -> dict[str, Any]:
    best_seasons = parse_json_list(row.get("best_seasons"))
    recommended_times = parse_json_list(row.get("recommended_times"))
    interest_ids = [
        int(item)
        for item in str(row.get("interest_ids") or "").split(",")
        if item.strip().isdigit()
    ]
    image_urls = [
        image_url(item)
        for item in str(row.get("image_urls") or "").split("|")
        if item.strip()
    ]

    database_id = int(row["id"])
    category_id = int(row["category_id"])
    duration = int(to_float(row.get("expected_duration_minutes"), 60))
    activity_level = ACTIVITY_LEVEL.get(str(row.get("activity_level") or "sensible"), 2)

    return {
        "database_place_id": database_id,
        "place_id": f"db/{database_id}",
        "osm_place_id": None,
        "name": row["name"],
        "category": CATEGORY_BY_ID.get(category_id, "general"),
        "interests": [INTEREST_BY_ID[item] for item in interest_ids if item in INTEREST_BY_ID],
        "latitude": to_float(row.get("latitude")),
        "longitude": to_float(row.get("longitude")),
        "cost": int(to_float(row.get("cost"))),
        "duration": duration,
        "activity_level": activity_level,
        "is_outdoor": int(bool(row.get("is_outdoor"))),
        "average_rating": to_float(row.get("average_rating")),
        "reviews_count": int(to_float(row.get("reviews_count"))),
        "best_season": best_seasons[0] if best_seasons else "all_year",
        "recommended_time": recommended_times[0] if recommended_times else "afternoon",
        "opening_hours": opening_hours_to_string(row.get("opening_hours")),
        "image_urls": image_urls,
    }


def read_database_places() -> list[dict[str, Any]]:
    query = """
        SELECT
            p.id,
            p.category_id,
            p.name,
            p.cost,
            p.expected_duration_minutes,
            p.activity_level,
            p.is_outdoor,
            p.best_seasons,
            p.recommended_times,
            p.opening_hours,
            p.average_rating,
            p.reviews_count,
            p.latitude,
            p.longitude,
            GROUP_CONCAT(DISTINCT ip.interest_id ORDER BY ip.interest_id) AS interest_ids,
            GROUP_CONCAT(DISTINCT pi.image_url SEPARATOR '|') AS image_urls
        FROM places p
        LEFT JOIN interest_place ip ON ip.place_id = p.id
        LEFT JOIN place_images pi ON pi.place_id = p.id
        WHERE p.latitude IS NOT NULL
          AND p.longitude IS NOT NULL
        GROUP BY
            p.id,
            p.category_id,
            p.name,
            p.cost,
            p.expected_duration_minutes,
            p.activity_level,
            p.is_outdoor,
            p.best_seasons,
            p.recommended_times,
            p.opening_hours,
            p.average_rating,
            p.reviews_count,
            p.latitude,
            p.longitude
        ORDER BY p.id
    """

    connection = mysql.connector.connect(**db_config())
    try:
        cursor = connection.cursor(dictionary=True)
        try:
            cursor.execute(query)
            return [normalize_place(row) for row in cursor.fetchall()]
        finally:
            cursor.close()
    finally:
        connection.close()
