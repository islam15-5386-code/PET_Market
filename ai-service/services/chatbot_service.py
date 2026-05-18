from schemas.chatbot_schema import ChatbotRequest
from services.intent_classifier import classify_intent
from services.llm_provider import generate_json
from services.product_filter_extractor import extract_filters
from services.response_generator import generate_reply
from services.safety_detector import detect_safety


def _default_response(message: str):
    lower = (message or "").lower()
    bangla_hint = any(x in lower for x in ["হাই", "হ্যালো", "আসসালামু", "কুকুর", "বিড়াল", "বিড়াল", "পেট"])
    reply = (
        "আমি সবসময় সাহায্য করতে প্রস্তুত। আপনার pet type, need (food/grooming/health), এবং budget লিখুন।"
        if bangla_hint else
        "I'm always here to help. Share your pet type, need (food/grooming/health), and budget."
    )
    return {
        "reply": reply,
        "intent": "general_pet_care",
        "pet_type": None,
        "category": None,
        "age_group": None,
        "location": None,
        "price_min": None,
        "price_max": None,
        "safety_level": "safe",
        "vet_warning": None,
        "recommended_product_filters": {},
        "confidence": 0.5,
    }


def _is_greeting(message: str) -> bool:
    text = message.lower().strip(" \t\r\n.,!?;:'\"-_=+()[]{}")
    greetings = {
        "hi", "hello", "hey", "hola", "good morning", "good afternoon", "good evening",
        "assalamu alaikum", "as salamualaikum", "salam", "আসসালামু আলাইকুম", "হাই", "হ্যালো",
    }
    if text in greetings:
        return True
    return any(text.startswith(g + " ") for g in greetings)


def _greeting_reply(message: str) -> str:
    lower = message.lower()
    if any(x in lower for x in ["হাই", "হ্যালো", "আসসালামু", "salam"]):
        return "হাই! আমি PetCare AI Assistant. আপনি pet food, grooming, health guidance বা product recommendation জানতে পারেন."
    return "Hi! I'm your PetCare AI Assistant. Ask me about pet food, grooming, health guidance, or product recommendations."


def _llm_prompt(message: str, intent: str, filters: dict, safety_level: str, vet_warning: str | None) -> str:
    return f"""
Return JSON only with keys:
reply, intent, pet_type, category, age_group, location, price_min, price_max, safety_level, vet_warning, recommended_product_filters.

Rules:
- Keep reply concise and practical.
- If emergency symptoms exist, advise contacting a veterinarian immediately.
- Do not provide diagnosis or medicine dosage.
- If user just greets, reply warmly and ask pet type + need + budget.

User message: {message}
Detected intent: {intent}
Detected filters: {filters}
Safety level: {safety_level}
Vet warning: {vet_warning}
"""


def _normalize_llm_payload(llm: dict, fallback: dict) -> dict:
    return {
        "reply": str(llm.get("reply") or fallback["reply"]),
        "intent": str(llm.get("intent") or fallback["intent"]),
        "pet_type": llm.get("pet_type", fallback["pet_type"]),
        "category": llm.get("category", fallback["category"]),
        "age_group": llm.get("age_group", fallback["age_group"]),
        "location": llm.get("location", fallback["location"]),
        "price_min": llm.get("price_min", fallback["price_min"]),
        "price_max": llm.get("price_max", fallback["price_max"]),
        "safety_level": str(llm.get("safety_level") or fallback["safety_level"]),
        "vet_warning": llm.get("vet_warning", fallback["vet_warning"]),
        "recommended_product_filters": llm.get("recommended_product_filters") or fallback["recommended_product_filters"],
        "confidence": float(llm.get("confidence") or fallback["confidence"]),
    }


def run_chatbot(payload: ChatbotRequest):
    try:
        message = (payload.message or "").strip()
    except Exception:
        message = ""

    if not message:
        return _default_response(message)

    if _is_greeting(message):
        greeting = {
            "reply": _greeting_reply(message),
            "intent": "general_pet_care",
            "pet_type": None,
            "category": None,
            "age_group": None,
            "location": None,
            "price_min": None,
            "price_max": None,
            "safety_level": "safe",
            "vet_warning": None,
            "recommended_product_filters": {},
            "confidence": 1.0,
        }
        # Even for greetings, try Gemini/OpenAI enhancement first.
        try:
            llm = generate_json(
                _llm_prompt(message, greeting["intent"], {}, greeting["safety_level"], greeting["vet_warning"]),
                feature="pet_chatbot",
                max_output_tokens=220,
            )
            if isinstance(llm, dict) and not llm.get("_skipped"):
                return _normalize_llm_payload(llm, greeting)
        except Exception:
            pass
        return greeting

    try:
        filters = extract_filters(message) or {}
    except Exception:
        filters = {}

    try:
        intent, confidence = classify_intent(message)
    except Exception:
        intent, confidence = "general_pet_care", 0.5

    try:
        safety_level, vet_warning = detect_safety(message)
    except Exception:
        safety_level, vet_warning = "safe", None

    if vet_warning and any(k in message.lower() for k in ["bleeding", "breathing", "poison", "seizure", "unconscious", "vomiting", "not eating", "khacche na"]):
        intent = "emergency_warning"
    elif vet_warning:
        intent = "health_warning"

    try:
        reply = generate_reply(
            intent=intent,
            pet_type=filters.get("pet_type"),
            category=filters.get("category"),
            safety_level=safety_level,
            vet_warning=vet_warning,
            low_confidence=confidence < 0.45,
        )
    except Exception:
        reply = _default_response(message)["reply"]

    # Optional LLM enhancement: if Gemini/OpenAI is configured, use it; otherwise keep local template.
    try:
        llm = generate_json(
            _llm_prompt(message, intent, filters, safety_level, vet_warning),
            feature="pet_chatbot",
            max_output_tokens=220,
        )
        if isinstance(llm, dict) and not llm.get("_skipped"):
            reply = str(llm.get("reply") or reply)
            intent = str(llm.get("intent") or intent)
            for k in ["pet_type", "category", "age_group", "location", "price_min", "price_max"]:
                if llm.get(k) is not None:
                    filters[k] = llm.get(k)
            safety_level = str(llm.get("safety_level") or safety_level)
            vet_warning = llm.get("vet_warning") or vet_warning
    except Exception:
        pass

    out = {
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

    # Primary realtime path: LLM reply for all non-empty texts (Gemini/OpenAI based on provider config).
    try:
        llm = generate_json(
            _llm_prompt(message, out["intent"], out["recommended_product_filters"], out["safety_level"], out["vet_warning"]),
            feature="pet_chatbot",
            max_output_tokens=260,
        )
        if isinstance(llm, dict) and not llm.get("_skipped"):
            out = _normalize_llm_payload(llm, out)
    except Exception:
        pass

    if not out.get("reply"):
        return _default_response(message)
    return out
