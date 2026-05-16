def generate_reply(intent: str, pet_type: str | None, category: str | None, safety_level: str, vet_warning: str | None, low_confidence: bool = False):
    pet = pet_type or "your pet"

    if intent == "emergency_warning":
        return "This may be serious. Please contact a veterinarian immediately."

    if intent == "food_advice":
        base = f"For {pet}, choose balanced, age-appropriate {category or 'food'} with good protein and digestible ingredients."
    elif intent == "grooming_advice":
        base = f"For {pet}, regular grooming depends on coat type. Brush routinely and use gentle pet-safe products."
    elif intent in ["health_warning", "emergency_warning"]:
        base = f"I can share basic guidance for {pet}, but symptoms should be evaluated by a veterinarian."
    elif intent == "product_recommendation":
        base = f"I can suggest suitable {category or 'pet care'} products for {pet} based on your budget and needs."
    elif intent == "general_pet_care":
        base = f"For {pet}, keep a regular feeding, hydration, grooming, and activity routine."
    else:
        base = "Please share pet type, need (food/grooming/health), and budget so I can help better."

    if low_confidence:
        base += " Could you share a bit more detail about your pet type, age, and budget?"
    if vet_warning:
        base += f" {vet_warning}"
    if intent in ["health_warning", "emergency_warning"] and "according to veterinarian advice" not in base:
        base += " Use medicine only according to veterinarian advice."

    return base
