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

    if safety_level == 'emergency':
        intent = 'emergency_warning'
    elif safety_level == 'warning' and intent not in ['emergency_warning']:
        intent = 'health_warning'

    low_confidence = confidence < 0.45

    reply = generate_reply(
        intent=intent,
        pet_type=filters['pet_type'],
        category=filters['category'],
        safety_level=safety_level,
        vet_warning=vet_warning,
        low_confidence=low_confidence,
    )

    return {
        'reply': reply,
        'intent': intent,
        'pet_type': filters['pet_type'],
        'category': filters['category'],
        'age_group': filters['age_group'],
        'price_min': filters['price_min'],
        'price_max': filters['price_max'],
        'safety_level': safety_level,
        'vet_warning': vet_warning,
        'recommended_product_filters': {
            'pet_type': filters['pet_type'],
            'category': filters['category'],
            'age_group': filters['age_group'],
            'price_min': filters['price_min'],
            'price_max': filters['price_max'],
        },
        'confidence': round(float(confidence), 4),
    }
