from pathlib import Path

import joblib
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import LabelEncoder

BASE_DIR = Path(__file__).resolve().parents[1]
MODELS_DIR = BASE_DIR / "models"
DATA_FILE = BASE_DIR / "data" / "seed_training_data.csv"


def train_intent_model() -> dict:
    MODELS_DIR.mkdir(parents=True, exist_ok=True)
    df = pd.read_csv(DATA_FILE)
    df = df.dropna(subset=["text", "intent"])

    X = df["text"].astype(str)
    y = df["intent"].astype(str)

    vectorizer = TfidfVectorizer(ngram_range=(1, 2), min_df=1)
    Xv = vectorizer.fit_transform(X)

    le = LabelEncoder()
    y_enc = le.fit_transform(y)

    model = LogisticRegression(max_iter=1200)
    model.fit(Xv, y_enc)

    intent_model_path = MODELS_DIR / "intent_model.pkl"
    vectorizer_path = MODELS_DIR / "vectorizer.pkl"
    encoder_path = MODELS_DIR / "label_encoder.pkl"

    joblib.dump(model, intent_model_path)
    joblib.dump(vectorizer, vectorizer_path)
    joblib.dump(le, encoder_path)

    return {
        "rows": int(len(df)),
        "intent_model": str(intent_model_path),
        "vectorizer": str(vectorizer_path),
        "label_encoder": str(encoder_path),
    }


if __name__ == "__main__":
    print(train_intent_model())
