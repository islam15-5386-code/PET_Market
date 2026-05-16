from pathlib import Path

import joblib


class ModelLoader:
    def __init__(self) -> None:
        self.base = Path(__file__).resolve().parents[1] / "models"

    def _load(self, filename: str):
        path = self.base / filename
        if not path.exists():
            return None
        try:
            return joblib.load(path)
        except Exception:
            return None

    def search_bundle(self):
        model = self._load("search_intent_model.pkl") or self._load("intent_model.pkl")
        vectorizer = self._load("vectorizer.pkl")
        return model, vectorizer

    def chatbot_bundle(self):
        model = self._load("pet_chatbot_intent_model.pkl") or self._load("intent_model.pkl")
        vectorizer = self._load("pet_chatbot_vectorizer.pkl") or self._load("vectorizer.pkl")
        label_encoder = self._load("label_encoder.pkl")
        return model, vectorizer, label_encoder


loader = ModelLoader()


def load_model_bundle(bundle: str = "chatbot"):
    if bundle == "search":
        model, vectorizer = loader.search_bundle()
        return model, vectorizer, None
    return loader.chatbot_bundle()
