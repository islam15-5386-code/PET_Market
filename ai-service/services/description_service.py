from schemas.description_schema import DescriptionRequest
from services.fallback_template import generate_fallback
from services.prompt_builder import build_prompt
from services.provider_client import call_provider


def _validate_output(payload: dict) -> bool:
    required = [
        "professional_product_title", "short_description", "long_description",
        "seo_keywords", "benefits", "care_instruction", "usage_instruction",
        "safety_warning", "meta_title", "meta_description", "suggested_tags",
    ]
    return all(k in payload for k in required)


def generate_description(payload: DescriptionRequest) -> dict:
    prompt = build_prompt(payload)

    try:
        generated = call_provider(prompt)
        if _validate_output(generated):
            return generated
    except Exception:
        pass

    # single retry for invalid json/shape
    try:
        generated = call_provider(prompt)
        if _validate_output(generated):
            return generated
    except Exception:
        pass

    return generate_fallback(payload)
