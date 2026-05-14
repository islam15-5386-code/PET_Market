from fastapi.testclient import TestClient

from main import app


client = TestClient(app)


def test_smart_route_product_search_rule_based():
    res = client.post("/ai/route", json={
        "feature": "product_search",
        "input": {"query": "puppy shampoo under 500"},
    })
    assert res.status_code == 200
    data = res.json()
    assert data["strategy_used"] in ["rule_based", "local_model"]


def test_smart_route_chatbot_emergency_template():
    res = client.post("/ai/route", json={
        "feature": "pet_chatbot",
        "input": {"message": "my cat is bleeding"},
    })
    assert res.status_code == 200
    assert res.json()["strategy_used"] == "template"


def test_smart_route_description_template_when_provider_none():
    res = client.post("/ai/route", json={
        "feature": "product_description",
        "input": {
            "name": "Pet Grooming Shampoo 500ml",
            "category": "Pet Grooming",
            "pet_type": "Dog",
            "language": "English",
            "tone": "professional",
        },
    })
    assert res.status_code == 200
    assert res.json()["strategy_used"] in ["template", "cache", "llm_fallback"]
