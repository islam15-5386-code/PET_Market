from schemas.description_schema import DescriptionRequest
from services.fallback_template import generate_fallback


def test_fallback_contains_required_fields():
    payload = DescriptionRequest(
        product_name="Cat Food",
        category="Food",
        pet_type="Cat",
        age_group="Kitten",
        brand="MeowCare",
        price=750,
        weight_or_size="1kg",
        language="English",
        tone="SEO optimized",
    )

    result = generate_fallback(payload)
    assert result["professional_product_title"]
    assert len(result["seo_keywords"]) == 5
    assert len(result["benefits"]) == 3
    assert "meta_title" in result
    assert "meta_description" in result
