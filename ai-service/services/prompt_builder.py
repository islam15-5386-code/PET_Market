import json

from schemas.description_schema import DescriptionRequest


OUTPUT_SCHEMA = {
    "professional_product_title": "string",
    "short_description": "string",
    "long_description": "string",
    "seo_keywords": ["string", "string", "string", "string", "string"],
    "benefits": ["string", "string", "string"],
    "care_instruction": "string",
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
        "weight_or_size": payload.weight_or_size,
        "ingredients_or_materials": _listify(payload.ingredients_or_materials)[:8],
        "key_features": _listify(payload.key_features)[:8],
        "usage_instruction": payload.usage_instruction,
        "safety_note": payload.safety_note,
        "target_customer": payload.target_customer,
        "language": payload.language,
        "tone": payload.tone,
        "market": "Bangladesh",
    }

    compact = {k: v for k, v in compact.items() if v not in (None, "", [], {})}

    return (
        "Return only valid JSON. No markdown. No extra text. "
        "Generate marketplace-ready product copy using the requested language and tone. "
        "Avoid fake medical claims and false brand claims. "
        "For medicine/health-related products, include safety warning exactly with: "
        "'Consult a veterinarian for medical concerns.' "
        "Keep SEO keywords relevant and concise. "
        "Output schema: "
        + json.dumps(OUTPUT_SCHEMA, ensure_ascii=True)
        + " Input: "
        + json.dumps(compact, ensure_ascii=True)
    )
