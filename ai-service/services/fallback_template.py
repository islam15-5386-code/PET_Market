from schemas.description_schema import DescriptionRequest


def _listify(value):
    if value is None:
        return []
    if isinstance(value, list):
        return [str(v).strip() for v in value if str(v).strip()]
    return [v.strip() for v in str(value).split(",") if v.strip()]


def generate_fallback(payload: DescriptionRequest) -> dict:
    features = _listify(payload.key_features)
    features_text = ", ".join(features[:3]) if features else "balanced daily utility"
    title = " ".join([x for x in [payload.brand, payload.product_name, f"- {payload.weight_or_size}" if payload.weight_or_size else None] if x])
    short = f"{payload.product_name} is a dependable {payload.category.lower()} option for {payload.pet_type.lower()} care."
    long_desc = (
        f"{short} It is designed for Bangladesh marketplace needs with focus on {features_text}. "
        f"Suitable for everyday pet care with practical value."
    )
    warning = payload.safety_note or "Consult a veterinarian for medical concerns."

    return {
        "professional_product_title": title[:140],
        "short_description": short,
        "long_description": long_desc,
        "seo_keywords": [
            f"{payload.pet_type.lower()} {payload.category.lower()}",
            f"{payload.product_name.lower()}",
            "pet care bangladesh",
            f"{payload.category.lower()} bangladesh",
            f"{payload.pet_type.lower()} product",
        ],
        "benefits": [
            "Supports daily pet care",
            "Easy to include in routine",
            "Balanced quality and value",
        ],
        "care_instruction": "Store in a cool, dry place away from direct sunlight.",
        "usage_instruction": payload.usage_instruction or "Use as directed on label.",
        "safety_warning": warning,
        "meta_title": title[:60],
        "meta_description": long_desc[:155],
        "suggested_tags": [payload.pet_type.lower(), payload.category.lower(), "pet-care", "bangladesh"],
        "provider_name": "fallback",
        "model_name": "template",
        "token_usage": {
            "prompt_tokens": 0,
            "completion_tokens": 0,
            "total_tokens": 0,
        },
    }
