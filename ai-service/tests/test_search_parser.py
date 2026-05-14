from services.query_parser import parse_query


def test_parse_english_query_extracts_fields():
    parsed = parse_query("I need good food for my kitten under 1000 BDT in Dhaka")
    assert parsed["intent"] in ["product_search", "general_query"]
    assert parsed["pet_type"] == "cat"
    assert parsed["age_group"] == "kitten"
    assert parsed["category"] == "food"
    assert parsed["price_max"] == 1000
    assert parsed["location"] == "Dhaka"


def test_parse_bangla_mixed_price_and_pet():
    parsed = parse_query("amar biral er jonno valo food chai ১০০০ টাকার মধ্যে")
    assert parsed["pet_type"] == "cat"
    assert parsed["category"] == "food"
    assert parsed["price_max"] == 1000


def test_parse_location_and_breed():
    parsed = parse_query("grooming items for Persian cat in Dhaka")
    assert parsed["pet_type"] == "cat"
    assert parsed["category"] in ["pet-grooming", "grooming"]
    assert parsed["location"] == "Dhaka"
    assert parsed["breed"] == "Persian"
