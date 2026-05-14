from pathlib import Path

import joblib


class ModelLoader:
    def __init__(self) -> None:
        self.base = Path(__file__).resolve().parents[1] / "models"

    def _load(self, filename: str):
        p = self.base / filename
        if not p.exists():
            return None
        return joblib.load(p)

    def search_bundle(self):
        model = self._load("search_intent_model.pkl")
        vectorizer = self._load("vectorizer.pkl")
        return model, vectorizer

    def chatbot_bundle(self):
        model = self._load("intent_model.pkl") or self._load("pet_chatbot_intent_model.pkl")
        vectorizer = self._load("pet_chatbot_vectorizer.pkl")
        label_encoder = self._load("label_encoder.pkl")
        return model, vectorizer, label_encoder

    def intent_model(self):
        return self.chatbot_bundle()[0]

    def model(self):
        return self.search_bundle()[0]

    def vectorizer(self):
        return self.search_bundle()[1]

    def label_encoder(self):
        return self.chatbot_bundle()[2]


loader = ModelLoader()


def load_model_bundle(bundle: str = "chatbot"):
    if bundle == "search":
        model, vectorizer = loader.search_bundle()
        return model, vectorizer, None
    return loader.chatbot_bundle()
