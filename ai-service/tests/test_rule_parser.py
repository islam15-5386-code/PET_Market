from services.rule_parser import parse_search_query


def test_rule_parser_price_and_pet():
    out = parse_search_query("I need food for my kitten under 1000 BDT")
    assert out["pet_type"] == "cat"
    assert out["category"] in ["food", "cat-food"]
    assert out["price_max"] == 1000


def test_rule_parser_bangla_mix():
    out = parse_search_query("amar biral er jonno food chai")
    assert out["pet_type"] == "cat"
