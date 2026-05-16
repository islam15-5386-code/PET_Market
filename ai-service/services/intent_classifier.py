from services.model_loader import load_model_bundle

SUPPORTED_INTENTS = {
    "product_search",
    "product_description",
    "food_advice",
    "grooming_advice",
    "health_warning",
    "emergency_warning",
    "product_recommendation",
    "general_pet_care",
    "unknown",
}


def _rule_fallback(message: str) -> tuple[str, float]:
    text = message.lower()
    if any(k in text for k in ["vomit", "vomiting", "bleeding", "seizure", "unconscious", "poison", "breathing"]):
        return "emergency_warning", 0.9
    if any(k in text for k in ["not eating", "khacche na", "fever", "diarrhea", "injury"]):
        return "health_warning", 0.85
    if any(k in text for k in ["food", "khabar"]):
        return "food_advice", 0.75
    if any(k in text for k in ["shampoo", "grooming", "brush", "comb"]):
        return "grooming_advice", 0.75
    if any(k in text for k in ["recommend", "suggest", "which product", "best product"]):
        return "product_recommendation", 0.7
    return "general_pet_care", 0.55


def classify_intent(message: str) -> tuple[str, float]:
    model, vectorizer, label_encoder = load_model_bundle("chatbot")
    text = message.lower().strip()
    if model is None or vectorizer is None:
        return _rule_fallback(text)

    try:
        vec = vectorizer.transform([text])
        pred = model.predict(vec)[0]
        if label_encoder is not None:
            intent = str(label_encoder.inverse_transform([pred])[0])
        else:
            intent = str(pred)

        confidence = 0.7
        if hasattr(model, "predict_proba"):
            confidence = float(max(model.predict_proba(vec)[0]))

        if intent not in SUPPORTED_INTENTS:
            return _rule_fallback(text)
        return intent, confidence
    except Exception:
        return _rule_fallback(text)
