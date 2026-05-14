from pathlib import Path

import joblib
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression

MODEL_DIR = Path(__file__).resolve().parent / "models"
MODEL_DIR.mkdir(parents=True, exist_ok=True)

SAMPLES = [
    ("I need good food for my kitten under 1000 BDT", "product_search"),
    ("Best shampoo for puppy", "product_search"),
    ("Cheap cat medicine for fever", "product_search"),
    ("Dog food under 1500 taka", "product_search"),
    ("amar biral er jonno valo food chai 1000 takar moddhe", "product_search"),
    ("puppy collar below 800", "product_search"),
    ("fish tank accessories in budget", "product_search"),
    ("rabbit toy under 500", "product_search"),
    ("show me bird food", "product_search"),
    ("cat grooming brush", "product_search"),
    ("hello", "general_query"),
    ("what is your return policy", "general_query"),
    ("how to contact support", "general_query"),
    ("where is my order", "general_query"),
]

texts = [x[0] for x in SAMPLES]
labels = [x[1] for x in SAMPLES]

vectorizer = TfidfVectorizer(ngram_range=(1, 2), min_df=1)
X = vectorizer.fit_transform(texts)

model = LogisticRegression(max_iter=1000)
model.fit(X, labels)

joblib.dump(model, MODEL_DIR / "search_intent_model.pkl")
joblib.dump(vectorizer, MODEL_DIR / "vectorizer.pkl")

print("Saved model and vectorizer to", MODEL_DIR)
