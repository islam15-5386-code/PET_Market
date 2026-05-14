from typing import Any


def chatbot_template(intent: str, pet_type: str | None = None) -> dict[str, Any]:
    pet = pet_type or "pet"
    templates = {
        "food_advice": f"Choose age-appropriate balanced food for your {pet}. Transition food gradually over 5-7 days.",
        "grooming_advice": f"Brush your {pet} regularly, keep skin clean, and use pet-safe shampoo only.",
        "health_warning": "I can share general care tips, but for illness signs please consult a veterinarian.",
        "general_pet_care": f"For your {pet}, maintain routine feeding, clean water, hygiene, and regular vet checkups.",
    }
    return {
        "reply": templates.get(intent, "Please share your pet type, age, and budget so I can help better."),
        "intent": intent,
    }


def description_template(payload: dict[str, Any]) -> dict[str, Any]:
    name = payload.get("name") or payload.get("product_name") or "Pet Product"
    category = payload.get("category") or "Pet Supplies"
    pet_type = payload.get("pet_type") or "Pet"
    warning = "Use pet medicine only according to veterinarian advice." if "health" in str(category).lower() else "Use as directed on label."
    return {
        "professional_product_title": name,
        "short_description": f"Reliable {category} item for {pet_type} care.",
        "long_description": f"{name} is designed for daily {pet_type} care with practical quality and value.",
        "seo_keywords": [str(name).lower(), str(category).lower(), "pet care", "bangladesh", str(pet_type).lower()],
        "benefits": ["Supports daily care", "Easy to use", "Good value"],
        "usage_instruction": payload.get("usage_instruction") or "Use based on package instructions.",
        "safety_warning": warning,
        "meta_title": str(name),
        "meta_description": f"{name} for {pet_type} care.",
        "tags": [str(category).lower().replace(" ", "-"), str(pet_type).lower()],
    }
