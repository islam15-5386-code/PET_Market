from services.model_loader import load_model_bundle

INTENTS = {
    "food_advice",
    "grooming_advice",
    "product_recommendation",
    "health_warning",
    "emergency_warning",
    "general_pet_care",
    "unknown",
}


def classify_intent(message: str):
    model, vectorizer, label_encoder = load_model_bundle()
    if not model or not vectorizer:
        return "unknown", 0.0

    vec = vectorizer.transform([message.lower().strip()])
    pred = model.predict(vec)[0]

    intent = "unknown"
    if label_encoder is not None:
        try:
            intent = str(label_encoder.inverse_transform([pred])[0])
        except Exception:
            intent = str(pred)
    else:
        intent = str(pred)

    confidence = 0.0
    if hasattr(model, "predict_proba"):
        confidence = float(max(model.predict_proba(vec)[0]))

    if intent not in INTENTS:
        intent = "unknown"
    return intent, confidence
