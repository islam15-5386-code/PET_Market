import json

from schemas.description_schema import DescriptionRequest

OUTPUT_SCHEMA = {
    "professional_product_title": "string",
    "short_description": "string",
    "long_description": "string",
    "seo_keywords": ["string"],
    "benefits": ["string"],
    "usage_instruction": "string",
    "safety_warning": "string",
    "meta_title": "string",
    "meta_description": "string",
    "suggested_tags": ["string"],
}


def _listify(value):
    if value is None:
        return []
    if isinstance(value, list):
        return [str(v).strip() for v in value if str(v).strip()]
    return [v.strip() for v in str(value).split(",") if v.strip()]


def build_prompt(payload: DescriptionRequest) -> str:
    compact = {
        "product_name": payload.product_name,
        "category": payload.category,
        "pet_type": payload.pet_type,
        "age_group": payload.age_group,
        "brand": payload.brand,
        "price": payload.price,
        "key_features": _listify(payload.key_features)[:6],
        "usage_instruction": payload.usage_instruction,
        "language": payload.language,
        "tone": payload.tone,
    }
    compact = {k: v for k, v in compact.items() if v not in (None, "", [], {})}

    return (
        "Return valid JSON only. No markdown. No extra text. "
        "Avoid fake medical claims. Do not provide medicine dosage. "
        "For medicine/health products include this warning: Use pet medicine only according to veterinarian advice. "
        "Output schema: " + json.dumps(OUTPUT_SCHEMA, ensure_ascii=True) +
        " Input: " + json.dumps(compact, ensure_ascii=True)
    )
