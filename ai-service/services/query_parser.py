import re
from typing import Optional

from services.model_loader import load_model_bundle

CATEGORY_MAP = {
    "fish-aquatics": ["aquarium", "fish food", "fish tank", "aquatic", "flakes"],
    "bird-supplies": ["bird food", "bird cage", "millet", "perch"],
    "food": ["food", "kibble", "meal", "khabar", "খাবার"],
    "grooming": ["shampoo", "grooming", "brush", "comb"],
    "health": ["medicine", "vitamin", "supplement", "health"],
    "toys": ["toy", "ball", "chew"],
    "collars": ["collar", "leash", "harness"],
    "beds": ["bed", "mat"],
}

PET_MAP = {
    "cat": ["cat", "kitten", "biral", "বিড়াল", "বিড়াল"],
    "dog": ["dog", "puppy", "kukur", "কুকুর"],
    "bird": ["bird", "parrot", "pakhi", "পাখি"],
    "fish": ["fish", "mach", "মাছ"],
    "small animal": ["rabbit", "hamster", "guinea pig"],
}

AGE_MAP = {
    "puppy": ["puppy"],
    "kitten": ["kitten"],
    "adult": ["adult"],
    "senior": ["senior", "old"],
}

LOCATIONS = {
    "Dhaka": ["dhaka"],
    "Chattogram": ["chattogram", "chittagong"],
    "Sylhet": ["sylhet"],
    "Rajshahi": ["rajshahi"],
    "Khulna": ["khulna"],
    "Barishal": ["barishal", "barisal"],
    "Rangpur": ["rangpur"],
    "Mymensingh": ["mymensingh"],
    "Gazipur": ["gazipur"],
    "Narayanganj": ["narayanganj"],
    "Mirpur": ["mirpur"],
    "Uttara": ["uttara"],
    "Dhanmondi": ["dhanmondi"],
    "Banani": ["banani"],
    "Gulshan": ["gulshan"],
    "Mohammadpur": ["mohammadpur", "mohammedpur"],
}

STOPWORDS = {
    "in", "for", "under", "below", "with", "and", "the", "need", "want", "items",
    "amar", "jonno", "chai", "moddhe", "takar", "er", "ami", "ki", "korbo",
}


def _normalize(text: str) -> str:
    text = text.lower().strip()
    text = text.translate(str.maketrans("০১২৩৪৫৬৭৮৯", "0123456789"))
    text = text.replace("৳", " bdt ").replace("টাকা", " taka ")
    return re.sub(r"\s+", " ", text)


def _detect_mapping(text: str, mapping: dict[str, list[str]]) -> Optional[str]:
    # Prefer more specific phrases such as "fish food" over generic "food".
    ordered_items = sorted(
        mapping.items(),
        key=lambda item: max(len(term) for term in item[1]),
        reverse=True,
    )
    for key, terms in ordered_items:
        if any(term in text for term in terms):
            return key
    return None


def _extract_location(text: str) -> Optional[str]:
    for city, aliases in LOCATIONS.items():
        if any(alias in text for alias in aliases):
            return city
    return None


def _extract_price_min(text: str) -> Optional[float]:
    m = re.search(r"(?:over|above|more than)\s*(\d+[\d,]*)", text)
    if m:
        return float(m.group(1).replace(",", ""))
    return None


def _extract_price_max(text: str) -> Optional[float]:
    patterns = [
        r"(?:under|below|less than)\s*(\d+[\d,]*)",
        r"(\d+[\d,]*)\s*bdt",
        r"(\d+[\d,]*)\s*taka(?:r)?\s*(?:moddhe|within)?",
        r"(\d+[\d,]*)\s*টাকার\s*মধ্যে",
    ]
    for pattern in patterns:
        m = re.search(pattern, text)
        if m:
            return float(m.group(1).replace(",", ""))
    return None


def _extract_keywords(text: str) -> list[str]:
    words = re.findall(r"[a-zA-Z]+", text)
    out: list[str] = []
    for word in words:
        if word in STOPWORDS or len(word) < 3:
            continue
        if word not in out:
            out.append(word)
    return out[:10]


def _predict_intent(query: str) -> tuple[str, float]:
    try:
        model, vectorizer, _ = load_model_bundle("search")
        if model is None or vectorizer is None:
            return "product_search", 0.9
        vec = vectorizer.transform([query])
        pred = str(model.predict(vec)[0])
        confidence = 0.9
        if hasattr(model, "predict_proba"):
            confidence = float(max(model.predict_proba(vec)[0]))
        return pred if pred else "product_search", confidence
    except Exception:
        return "product_search", 0.8


def parse_query(query: str) -> dict:
    normalized = _normalize(query)
    intent, confidence = _predict_intent(normalized)
    if intent != "product_search":
        intent = "product_search"

    category = _detect_mapping(normalized, CATEGORY_MAP)
    pet_type = _detect_mapping(normalized, PET_MAP)

    if pet_type == "fish" and category == "food":
        category = "fish-aquatics"
    elif pet_type == "bird" and category == "food":
        category = "bird-supplies"

    return {
        "intent": intent,
        "category": category,
        "pet_type": pet_type,
        "age_group": _detect_mapping(normalized, AGE_MAP),
        "location": _extract_location(normalized),
        "price_min": _extract_price_min(normalized),
        "price_max": _extract_price_max(normalized),
        "keywords": _extract_keywords(normalized),
        "confidence": round(confidence, 4),
    }
