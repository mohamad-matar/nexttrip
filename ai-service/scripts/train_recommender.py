from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

import joblib
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    classification_report,
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
    roc_auc_score,
)
from sklearn.model_selection import train_test_split


FEATURE_COLUMNS = [
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
]

LABEL_COLUMN = "recommended"


def load_dataset(path: Path) -> tuple[pd.DataFrame, pd.Series]:
    dataset = pd.read_csv(path)
    missing = [column for column in FEATURE_COLUMNS + [LABEL_COLUMN] if column not in dataset.columns]
    if missing:
        raise ValueError(f"Missing required columns: {', '.join(missing)}")

    x = dataset[FEATURE_COLUMNS].copy()
    y = dataset[LABEL_COLUMN].astype(int)
    return x, y


def build_model(seed: int) -> RandomForestClassifier:
    return RandomForestClassifier(
        n_estimators=250,
        max_depth=14,
        min_samples_split=8,
        min_samples_leaf=3,
        class_weight="balanced",
        random_state=seed,
        n_jobs=-1,
    )


def evaluate_model(
    model: RandomForestClassifier,
    x_test: pd.DataFrame,
    y_test: pd.Series,
) -> dict[str, Any]:
    predictions = model.predict(x_test)
    probabilities = model.predict_proba(x_test)[:, 1]

    importances = sorted(
        zip(FEATURE_COLUMNS, model.feature_importances_),
        key=lambda item: item[1],
        reverse=True,
    )

    return {
        "accuracy": round(accuracy_score(y_test, predictions), 4),
        "precision": round(precision_score(y_test, predictions), 4),
        "recall": round(recall_score(y_test, predictions), 4),
        "f1": round(f1_score(y_test, predictions), 4),
        "roc_auc": round(roc_auc_score(y_test, probabilities), 4),
        "confusion_matrix": confusion_matrix(y_test, predictions).tolist(),
        "classification_report": classification_report(y_test, predictions, output_dict=True),
        "feature_importance": [
            {"feature": feature, "importance": round(float(importance), 5)}
            for feature, importance in importances
        ],
    }


def save_artifacts(
    model: RandomForestClassifier,
    metrics: dict[str, Any],
    model_path: Path,
    metrics_path: Path,
) -> None:
    model_path.parent.mkdir(parents=True, exist_ok=True)
    metrics_path.parent.mkdir(parents=True, exist_ok=True)

    artifact = {
        "model": model,
        "feature_columns": FEATURE_COLUMNS,
        "label_column": LABEL_COLUMN,
        "positive_class": 1,
    }
    joblib.dump(artifact, model_path)

    with metrics_path.open("w", encoding="utf-8") as handle:
        json.dump(metrics, handle, indent=2)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Train a place recommendation model.")
    parser.add_argument("--input", default="data/training_dataset.csv", help="Training CSV path")
    parser.add_argument("--model", default="models/recommender_random_forest.joblib", help="Output model path")
    parser.add_argument("--metrics", default="models/recommender_metrics.json", help="Output metrics JSON path")
    parser.add_argument("--test-size", type=float, default=0.2, help="Test split ratio")
    parser.add_argument("--seed", type=int, default=42, help="Random seed")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    x, y = load_dataset(Path(args.input))
    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=args.test_size,
        random_state=args.seed,
        stratify=y,
    )

    model = build_model(args.seed)
    model.fit(x_train, y_train)
    metrics = evaluate_model(model, x_test, y_test)
    save_artifacts(model, metrics, Path(args.model), Path(args.metrics))

    print(f"Rows: {len(x)}")
    print(f"Train rows: {len(x_train)}")
    print(f"Test rows: {len(x_test)}")
    print(f"Accuracy: {metrics['accuracy']}")
    print(f"Precision: {metrics['precision']}")
    print(f"Recall: {metrics['recall']}")
    print(f"F1: {metrics['f1']}")
    print(f"ROC AUC: {metrics['roc_auc']}")
    print(f"Saved model: {args.model}")
    print(f"Saved metrics: {args.metrics}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
