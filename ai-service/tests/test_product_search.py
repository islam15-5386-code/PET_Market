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


def test_fish_food_prefers_fish_aquatics_category():
    res = client.post("/ai/product-search/parse", json={"query": "aquarium fish food under 300 bdt"})
    data = res.json()
    assert res.status_code == 200
    assert data["category"] == "fish-aquatics"
    assert data["pet_type"] == "fish"
    assert data["price_max"] == 300


def test_bird_food_prefers_bird_supplies_category():
    res = client.post("/ai/product-search/parse", json={"query": "bird food under 500 bdt"})
    data = res.json()
    assert res.status_code == 200
    assert data["category"] == "bird-supplies"
    assert data["pet_type"] == "bird"


def test_bangla_english_query_parsing():
    res = client.post("/ai/product-search/parse", json={"query": "amar biral khabar khacche na"})
    data = res.json()
    assert res.status_code == 200
    assert data["pet_type"] == "cat"
    assert "khabar" in " ".join(data["keywords"]).lower() or data["category"] == "food"
