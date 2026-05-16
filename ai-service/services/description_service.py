from schemas.description_schema import DescriptionRequest
from services.cache_service import cache_service
from services.fallback_template import generate_fallback
from services.prompt_builder import build_prompt
from services.provider_client import call_provider


REQUIRED_KEYS = [
    "professional_product_title",
    "short_description",
    "long_description",
    "seo_keywords",
    "benefits",
    "usage_instruction",
    "safety_warning",
    "meta_title",
    "meta_description",
    "suggested_tags",
]


def _validate_output(payload: dict) -> bool:
    return all(k in payload for k in REQUIRED_KEYS)


def _cache_payload(payload: DescriptionRequest) -> dict:
    return payload.model_dump(mode="json")


def generate_description(payload: DescriptionRequest) -> dict:
    cache_key_payload = _cache_payload(payload)
    cached = cache_service.get(cache_key_payload)
    if cached:
        return cached

    prompt = build_prompt(payload)
    result = None

    for _ in range(2):
        try:
            generated = call_provider(prompt)
            if _validate_output(generated):
                generated.setdefault("provider_name", "local_template_or_llm")
                generated.setdefault("model_name", "unknown")
                generated.setdefault("token_usage", {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0})
                result = generated
                break
        except Exception:
            continue

    if result is None:
        result = generate_fallback(payload)

    cache_service.set(cache_key_payload, result)
    return result
