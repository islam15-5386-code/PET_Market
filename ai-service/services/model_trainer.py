from pathlib import Path

import joblib
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder

from services.database_training_loader import load_from_db

BASE_DIR = Path(__file__).resolve().parents[1]
MODELS_DIR = BASE_DIR / 'models'
DATA_FILE = BASE_DIR / 'data' / 'seed_chatbot_training_data.csv'


def train_chatbot_model():
    MODELS_DIR.mkdir(parents=True, exist_ok=True)

    seed_df = pd.read_csv(DATA_FILE)
    db_df = load_from_db()
    df = pd.concat([seed_df, db_df], ignore_index=True)
    df = df.dropna(subset=['question', 'intent'])

    X = df['question'].astype(str)
    y = df['intent'].astype(str)

    vectorizer = TfidfVectorizer(ngram_range=(1, 2), min_df=1)
    Xv = vectorizer.fit_transform(X)

    le = LabelEncoder()
    y_enc = le.fit_transform(y)

    X_train, X_test, y_train, y_test = train_test_split(Xv, y_enc, test_size=0.2, random_state=42)

    model = LogisticRegression(max_iter=1200)
    model.fit(X_train, y_train)

    pred = model.predict(X_test)
    acc = float(accuracy_score(y_test, pred))

    model_path = MODELS_DIR / 'pet_chatbot_intent_model.pkl'
    vect_path = MODELS_DIR / 'pet_chatbot_vectorizer.pkl'
    label_path = MODELS_DIR / 'label_encoder.pkl'

    joblib.dump(model, model_path)
    joblib.dump(vectorizer, vect_path)
    joblib.dump(le, label_path)

    return {
        'model_path': str(model_path),
        'vectorizer_path': str(vect_path),
        'label_encoder_path': str(label_path),
        'rows': int(len(df)),
        'accuracy': acc,
    }
