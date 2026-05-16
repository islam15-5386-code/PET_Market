from schemas.chatbot_schema import ChatbotRequest
from services.intent_classifier import classify_intent
from services.product_filter_extractor import extract_filters
from services.response_generator import generate_reply
from services.safety_detector import detect_safety


def run_chatbot(payload: ChatbotRequest):
    message = payload.message.strip()
    filters = extract_filters(message)
    intent, confidence = classify_intent(message)

    safety_level, vet_warning = detect_safety(message)

    if vet_warning and any(k in message.lower() for k in ["bleeding", "breathing", "poison", "seizure", "unconscious", "vomiting", "not eating", "khacche na"]):
        intent = "emergency_warning"
    elif vet_warning:
        intent = "health_warning"

    reply = generate_reply(
        intent=intent,
        pet_type=filters.get("pet_type"),
        category=filters.get("category"),
        safety_level=safety_level,
        vet_warning=vet_warning,
        low_confidence=confidence < 0.45,
    )

    return {
        "reply": reply,
        "intent": intent,
        "pet_type": filters.get("pet_type"),
        "category": filters.get("category") if filters.get("category") != "medicine" else "health",
        "age_group": filters.get("age_group"),
        "location": filters.get("location"),
        "price_min": filters.get("price_min"),
        "price_max": filters.get("price_max"),
        "safety_level": safety_level,
        "vet_warning": vet_warning,
        "recommended_product_filters": {
            "pet_type": filters.get("pet_type"),
            "category": (filters.get("category") if filters.get("category") != "medicine" else "health"),
            "location": filters.get("location"),
            "age_group": filters.get("age_group"),
            "price_min": filters.get("price_min"),
            "price_max": filters.get("price_max"),
        },
        "confidence": round(float(confidence), 4),
    }
