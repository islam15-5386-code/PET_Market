from fastapi.testclient import TestClient

from main import app


client = TestClient(app)


def test_location_extraction():
    res = client.post("/ai/product-search/parse", json={"query": "bird food in Mirpur"})
    assert res.status_code == 200
    assert res.json()["location"] == "Mirpur"


def test_price_extraction():
    res = client.post("/ai/product-search/parse", json={"query": "kitten food under 1000 BDT"})
    assert res.status_code == 200
    assert res.json()["price_max"] == 1000


def test_category_and_pet_type_extraction():
    res = client.post("/ai/product-search/parse", json={"query": "puppy shampoo in Dhaka under 1000"})
    data = res.json()
    assert res.status_code == 200
    assert data["category"] == "grooming"
    assert data["pet_type"] == "dog"


def test_bangla_english_query_parsing():
    res = client.post("/ai/product-search/parse", json={"query": "amar biral khabar khacche na"})
    data = res.json()
    assert res.status_code == 200
    assert data["pet_type"] == "cat"
    assert "khabar" in " ".join(data["keywords"]).lower() or data["category"] == "food"
