from fastapi.testclient import TestClient

from main import app


client = TestClient(app)


def test_description_generator_fallback_with_provider_none():
    payload = {
        "product_name": "Cat Food",
        "category": "Food",
        "pet_type": "Cat",
        "age_group": "Kitten",
        "brand": "MeowCare",
        "price": 750,
        "features": ["High protein", "Easy digestion"],
        "language": "English",
        "tone": "SEO optimized",
    }
    res = client.post("/ai/product-description/generate", json=payload)
    assert res.status_code == 200
    data = res.json()
    assert data["provider_name"] == "local_template_or_llm"
    assert data["token_usage"]["total_tokens"] == 0
    assert data["professional_product_title"]


def test_missing_model_files_do_not_crash_search_parse():
    res = client.post("/ai/product-search/parse", json={"query": "puppy shampoo in Dhaka under 1000"})
    assert res.status_code == 200
    assert res.json()["intent"] == "product_search"
