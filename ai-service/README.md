# NextTrip AI Service

FastAPI service for place recommendations and smart trip planning.

## Source of Truth

By default, the service reads places from the Laravel database configured in the parent `.env` file:

```text
../.env
```

The trained model is loaded from:

```text
models/recommender_random_forest.joblib
```

## Run

```powershell
cd ai-service
python -m pip install -r requirements.txt
python -m uvicorn api:app --host 127.0.0.1 --port 8001
```

Health check:

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8001/health"
```

Set `AI_PLACES_SOURCE=csv` only for local fallback/testing.
