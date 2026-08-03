

from __future__ import annotations

import argparse
import csv
import hashlib
import json
import random
import sys
import time
from collections import defaultdict
from pathlib import Path
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request, urlopen


OVERPASS_URL = "https://overpass-api.de/api/interpreter"

TAG_PRIORITY = (
    "tourism",
    "historic",
    "natural",
    "leisure",
    "amenity",
    "shop",
    "sport",
    "craft",
)

CATEGORY_RULES = {
    "museum": ("culture", 12, 120, 1, False, "all_year", "morning"),
    "gallery": ("culture", 8, 90, 1, False, "all_year", "morning"),
    "attraction": ("attraction", 10, 120, 2, True, "spring_autumn", "afternoon"),
    "viewpoint": ("nature", 0, 60, 2, True, "spring_autumn", "sunset"),
    "zoo": ("family", 15, 180, 2, True, "spring_autumn", "morning"),
    "theme_park": ("family", 30, 240, 3, True, "spring_autumn", "afternoon"),
    "park": ("nature", 0, 90, 2, True, "spring_autumn", "afternoon"),
    "garden": ("nature", 3, 90, 1, True, "spring", "morning"),
    "nature_reserve": ("nature", 0, 180, 3, True, "spring_autumn", "morning"),
    "castle": ("historic", 8, 120, 2, True, "spring_autumn", "morning"),
    "archaeological_site": ("historic", 6, 120, 2, True, "spring_autumn", "morning"),
    "monument": ("historic", 0, 45, 1, True, "all_year", "afternoon"),
    "memorial": ("historic", 0, 30, 1, True, "all_year", "afternoon"),
    "restaurant": ("food", 18, 75, 1, False, "all_year", "evening"),
    "cafe": ("food", 8, 45, 1, False, "all_year", "afternoon"),
    "fast_food": ("food", 6, 35, 1, False, "all_year", "afternoon"),
    "theatre": ("culture", 10, 120, 1, False, "all_year", "evening"),
    "cinema": ("culture", 9, 120, 1, False, "all_year", "evening"),
    "library": ("culture", 0, 90, 1, False, "all_year", "morning"),
    "arts_centre": ("culture", 8, 120, 1, False, "all_year", "evening"),
    "place_of_worship": ("religious", 0, 45, 1, False, "all_year", "morning"),
    "marketplace": ("shopping", 12, 90, 2, True, "all_year", "morning"),
    "mall": ("shopping", 20, 120, 1, False, "all_year", "evening"),
    "hotel": ("accommodation", 60, 720, 1, False, "all_year", "evening"),
    "guest_house": ("accommodation", 35, 720, 1, False, "all_year", "evening"),
    "beach": ("nature", 0, 180, 2, True, "summer", "morning"),
    "peak": ("adventure", 0, 240, 4, True, "spring_autumn", "morning"),
    "swimming_pool": ("sport", 12, 120, 3, True, "summer", "afternoon"),
    "sports_centre": ("sport", 10, 120, 3, False, "all_year", "evening"),
}

DEFAULT_RULE = ("general", 5, 60, 1, False, "all_year", "afternoon")


def stable_rng(value: str) -> random.Random:
    digest = hashlib.sha256(value.encode("utf-8")).hexdigest()
    return random.Random(int(digest[:16], 16))


def post_overpass(query: str, retries: int = 3) -> dict[str, Any]:
    data = urlencode({"data": query}).encode("utf-8")
    headers = {"User-Agent": "academic-osm-dataset-builder/1.0"}
    request = Request(OVERPASS_URL, data=data, headers=headers, method="POST")

    for attempt in range(1, retries + 1):
        try:
            with urlopen(request, timeout=180) as response:
                return json.loads(response.read().decode("utf-8"))
        except (HTTPError, URLError, TimeoutError) as exc:
            if attempt == retries:
                raise RuntimeError(f"Overpass request failed: {exc}") from exc
            time.sleep(5 * attempt)

    raise RuntimeError("Overpass request failed")


def build_query(area: str, iso: str | None) -> str:
    if iso:
        area_selector = f'area["ISO3166-1"="{iso}"][admin_level=2]->.searchArea;'
    else:
        area_selector = f'area["name"="{area}"]->.searchArea;'
    return f"""
[out:json][timeout:180];
{area_selector}
(
  nwr(area.searchArea)["name"]["tourism"];
  nwr(area.searchArea)["name"]["historic"];
  nwr(area.searchArea)["name"]["natural"];
  nwr(area.searchArea)["name"]["leisure"];
  nwr(area.searchArea)["name"]["amenity"~"^(restaurant|cafe|fast_food|marketplace|theatre|cinema|library|place_of_worship|arts_centre)$"];
  nwr(area.searchArea)["name"]["shop"~"^(mall|supermarket|department_store|books|craft|souvenir)$"];
  nwr(area.searchArea)["name"]["sport"];
);
out center tags;
"""


def get_category(tags: dict[str, str]) -> tuple[str, str]:
    for key in TAG_PRIORITY:
        if key in tags:
            return key, tags[key]
    return "unknown", "unknown"


def normalize_name(tags: dict[str, str]) -> str:
    for key in ("name:en", "name"):
        value = tags.get(key)
        if value:
            return value.strip()
    return ""


def heuristic_fields(osm_key: str, osm_value: str, place_id: str) -> dict[str, Any]:
    rule = CATEGORY_RULES.get(osm_value, DEFAULT_RULE)
    category, base_cost, duration, activity, outdoor, season, recommended_time = rule
    rng = stable_rng(place_id)

    if osm_key == "natural":
        category = "nature"
        outdoor = True
    elif osm_key == "historic":
        category = "historic"
    elif osm_key == "sport":
        category = "sport"
        activity = max(activity, 3)

    cost = max(0, int(round(base_cost * rng.uniform(0.75, 1.35))))
    rating = round(min(5.0, max(3.0, rng.gauss(4.15, 0.45))), 1)
    reviews = int(max(3, rng.lognormvariate(3.4, 1.0)))

    return {
        "category": category,
        "cost": cost,
        "duration": duration,
        "activity_level": activity,
        "is_outdoor": int(outdoor),
        "average_rating": rating,
        "reviews_count": reviews,
        "best_season": season,
        "recommended_time": recommended_time,
    }


def element_to_row(element: dict[str, Any]) -> dict[str, Any] | None:
    tags = element.get("tags") or {}
    name = normalize_name(tags)
    if not name:
        return None

    lat = element.get("lat") or (element.get("center") or {}).get("lat")
    lon = element.get("lon") or (element.get("center") or {}).get("lon")
    if lat is None or lon is None:
        return None

    osm_key, osm_value = get_category(tags)
    place_id = f"{element.get('type', 'osm')}/{element['id']}"
    fields = heuristic_fields(osm_key, osm_value, place_id)

    return {
        "place_id": place_id,
        "name": name,
        "category": fields["category"],
        "latitude": round(float(lat), 7),
        "longitude": round(float(lon), 7),
        "cost": fields["cost"],
        "duration": fields["duration"],
        "activity_level": fields["activity_level"],
        "is_outdoor": fields["is_outdoor"],
        "average_rating": fields["average_rating"],
        "reviews_count": fields["reviews_count"],
        "best_season": fields["best_season"],
        "recommended_time": fields["recommended_time"],
        "opening_hours": tags.get("opening_hours", ""),
        "osm_type": element.get("type", ""),
        "osm_id": element.get("id", ""),
        "osm_tag_key": osm_key,
        "osm_tag_value": osm_value,
    }


def collect(area: str, iso: str | None, limit: int | None) -> list[dict[str, Any]]:
    payload = post_overpass(build_query(area, iso))
    rows = []
    seen = set()

    for element in payload.get("elements", []):
        row = element_to_row(element)
        if not row:
            continue
        key = (row["name"].casefold(), row["latitude"], row["longitude"])
        if key in seen:
            continue
        seen.add(key)
        rows.append(row)

    if limit and len(rows) > limit:
        rows = diversify_rows(rows, limit)
    return sorted(rows, key=lambda item: (item["category"], item["name"], item["place_id"]))


def diversify_rows(rows: list[dict[str, Any]], limit: int) -> list[dict[str, Any]]:
    grouped: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for row in rows:
        grouped[row["category"]].append(row)

    for category_rows in grouped.values():
        category_rows.sort(key=lambda item: (item["name"], item["place_id"]))

    selected = []
    categories = sorted(grouped)
    while len(selected) < limit and categories:
        next_categories = []
        for category in categories:
            if grouped[category] and len(selected) < limit:
                selected.append(grouped[category].pop(0))
            if grouped[category]:
                next_categories.append(category)
        categories = next_categories
    return selected


def write_csv(rows: list[dict[str, Any]], output: Path) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)
    fieldnames = [
        "place_id",
        "name",
        "category",
        "latitude",
        "longitude",
        "cost",
        "duration",
        "activity_level",
        "is_outdoor",
        "average_rating",
        "reviews_count",
        "best_season",
        "recommended_time",
        "opening_hours",
        "osm_type",
        "osm_id",
        "osm_tag_key",
        "osm_tag_value",
    ]
    with output.open("w", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Collect OpenStreetMap places into CSV.")
    parser.add_argument("--area", default="Syria", help="OSM area name, e.g. Syria, Damascus, Lebanon")
    parser.add_argument("--iso", default="SY", help="Country ISO3166-1 code; set empty string to use --area")
    parser.add_argument("--output", default="data/places.csv", help="Output CSV path")
    parser.add_argument("--limit", type=int, default=5000, help="Maximum rows to keep; use 0 for no limit")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    limit = args.limit if args.limit > 0 else None
    iso = args.iso.strip().upper() or None
    rows = collect(args.area, iso, limit)
    if not rows:
        print(f"No rows found for area: {args.area}", file=sys.stderr)
        return 1
    write_csv(rows, Path(args.output))
    print(f"Wrote {len(rows)} places to {args.output}")
    print("Note: cost, duration, rating, reviews, season, and recommended_time are heuristic fields.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
