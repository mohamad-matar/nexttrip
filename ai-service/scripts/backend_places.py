#!/usr/bin/env python3
"""Read recommendation places from the Laravel backend API."""

from __future__ import annotations

import json
import os
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


DEFAULT_PLACES_API_URL = "http://127.0.0.1:8000/api/internal/ai/places"


def places_api_url() -> str:
    return os.getenv("AI_BACKEND_PLACES_URL", DEFAULT_PLACES_API_URL)


def places_api_timeout() -> float:
    return float(os.getenv("AI_BACKEND_TIMEOUT", "30"))


def places_api_headers() -> dict[str, str]:
    headers = {
        "Accept": "application/json",
    }

    token = os.getenv("AI_INTERNAL_TOKEN")
    if token:
        headers["X-AI-Internal-Token"] = token

    return headers


def unwrap_response(payload: Any) -> list[dict[str, Any]]:
    if isinstance(payload, list):
        return payload

    if isinstance(payload, dict) and isinstance(payload.get("data"), list):
        return payload["data"]

    raise RuntimeError("Laravel places API returned an unexpected response shape.")


def read_backend_places() -> list[dict[str, Any]]:
    request = Request(places_api_url(), headers=places_api_headers(), method="GET")

    try:
        with urlopen(request, timeout=places_api_timeout()) as response:
            payload = json.loads(response.read().decode("utf-8"))
    except HTTPError as exc:
        detail = exc.read().decode("utf-8", errors="replace")
        raise RuntimeError(f"Laravel places API failed with HTTP {exc.code}: {detail}") from exc
    except URLError as exc:
        raise RuntimeError(f"Laravel places API is not available: {exc.reason}") from exc

    return unwrap_response(payload)
