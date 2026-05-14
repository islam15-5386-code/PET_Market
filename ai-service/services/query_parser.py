import re
from typing import Optional

from services.model_loader import loader

PET_PATTERNS = {
    "cat": ["cat", "kitten", "biral", "বিড়াল", "বিড়াল", "feline"],
    "dog": ["dog", "puppy", "kukur", "কুকুর", "canine"],
    "bird": ["bird", "parrot", "pakhi", "পাখি", "avian"],
    "fish": ["fish", "mach", "মাছ", "aquarium"],
    "rabbit": ["rabbit", "bunny", "khorgosh", "খরগোশ"],
}

AGE_PATTERNS = {
    "kitten": ["kitten", "baby cat"],
    "puppy": ["puppy", "baby dog"],
    "adult": ["adult", "grown"],
    "senior": ["senior", "old"],
}

CATEGORY_PATTERNS = {
    "dog-food": ["dog food", "puppy food"],
    "cat-food": ["cat food", "kitten food"],
    "bird-supplies": ["bird food", "bird seed", "bird cage", "parrot"],
    "fish-aquatics": ["fish food", "aquarium", "fish tank", "filter"],
    "pet-grooming": ["shampoo", "groom", "brush", "comb", "clipper"],
    "pet-health": ["medicine", "med", "fever", "vitamin", "health", "supplement", "flea", "tick"],
    "pet-toys": ["toy", "ball", "play", "chew"],
    "collars-leads": ["collar", "leash", "harness"],
    "pet-beds": ["bed", "mat", "sleep"],
    "small-animals": ["hamster", "rabbit", "guinea pig", "small animal"],
    "food": ["food", "meal", "feed", "খাবার", "kibble"],
}

BRANDS = [
    "royal canin",
    "pedigree",
    "whiskas",
    "purina",
    "orijen",
    "acana",
    "me-o",
    "drools",
    "frontline",
    "beaphar",
]

STOPWORDS = {
    "i", "need", "for", "my", "good", "best", "cheap", "under", "over", "within",
    "ami", "amar", "jonno", "chai", "valo", "bhalo", "moddhe", "takar", "er", "anything",
}

LOCATION_PATTERNS = {
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

BREED_PATTERNS = {
    "Persian": ["persian"],
    "Labrador": ["labrador"],
    "German Shepherd": ["german shepherd", "gsd"],
    "Siamese": ["siamese"],
}


def _normalize(text: str) -> str:
    t = text.lower().strip()
    t = t.replace("৳", " bdt ").replace("taka", " bdt ").replace("tk", " bdt ")
    bn_digits = str.maketrans("০১২৩৪৫৬৭৮৯", "0123456789")
    t = t.translate(bn_digits)
    t = re.sub(r"\s+", " ", t)
    return t


def _extract_price_min(text: str) -> Optional[float]:
    patterns = [
        r"(?:over|above|more than)\s*(\d+[\d,]*)",
    ]
    for p in patterns:
        m = re.search(p, text)
        if m:
            return float(m.group(1).replace(",", ""))
    return None


def _extract_price_max(text: str) -> Optional[float]:
    patterns = [
        r"(?:under|below|within|less than)\s*(\d+[\d,]*)",
        r"(\d+[\d,]*)\s*bdt",
        r"(\d+[\d,]*)\s*takar\s*moddhe",
        r"(\d+[\d,]*)\s*টাকা",
    ]
    for p in patterns:
        m = re.search(p, text)
        if m:
            return float(m.group(1).replace(",", ""))
    return None


def _detect_map(text: str, mapping: dict[str, list[str]]) -> Optional[str]:
    for key, terms in mapping.items():
        if any(term in text for term in terms):
            return key
    return None


def _extract_brand(text: str) -> Optional[str]:
    for brand in BRANDS:
        if brand in text:
            return brand.title()
    return None


def _extract_location(text: str) -> Optional[str]:
    for canonical, patterns in LOCATION_PATTERNS.items():
        if any(p in text for p in patterns):
            return canonical
    return None


def _extract_breed(text: str) -> Optional[str]:
    for breed, patterns in BREED_PATTERNS.items():
        if any(p in text for p in patterns):
            return breed
    return None


def _extract_keywords(text: str) -> list[str]:
    tokens = re.findall(r"[a-zA-Z]+", text)
    out: list[str] = []
    for token in tokens:
        tl = token.lower()
        if tl in STOPWORDS or len(tl) < 3:
            continue
        if tl not in out:
            out.append(tl)
    return out[:10]


def parse_query(query: str) -> dict:
    norm = _normalize(query)

    model, vectorizer = loader.search_bundle()
    intent = "product_search"
    confidence = 0.0
    if vectorizer is not None and model is not None:
        vec = vectorizer.transform([norm])
        intent = model.predict(vec)[0]
        if hasattr(model, "predict_proba"):
            proba = model.predict_proba(vec)[0]
            confidence = float(max(proba))

    pet_type = _detect_map(norm, PET_PATTERNS)
    age_group = _detect_map(norm, AGE_PATTERNS)
    category = _detect_map(norm, CATEGORY_PATTERNS)
    brand = _extract_brand(norm)
    location = _extract_location(norm)
    breed = _extract_breed(norm)
    price_min = _extract_price_min(norm)
    price_max = _extract_price_max(norm)
    keywords = _extract_keywords(norm)

    return {
        "intent": intent,
        "pet_type": pet_type,
        "age_group": age_group,
        "category": category,
        "brand": brand,
        "location": location,
        "breed": breed,
        "price_min": price_min,
        "price_max": price_max,
        "keywords": keywords,
        "confidence": round(confidence, 4),
    }
