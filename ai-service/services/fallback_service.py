from typing import Any

from services.response_templates import chatbot_template, description_template


def fallback_result(feature: str, payload: dict[str, Any]) -> dict[str, Any]:
    if feature == "product_description":
        return description_template(payload)
    if feature == "pet_chatbot":
        return chatbot_template("general_pet_care", payload.get("pet_type"))
    return {"filters": payload, "note": "Rule/local fallback used."}
