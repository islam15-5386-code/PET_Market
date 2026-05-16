from services.intent_classifier import classify_intent


def test_intent_classifier_missing_model_fallback():
    intent, confidence = classify_intent("My dog is vomiting")
    assert isinstance(intent, str)
    assert isinstance(confidence, float)
    assert intent in {
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

