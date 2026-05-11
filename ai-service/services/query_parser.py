import re
from difflib import get_close_matches
from typing import Optional

from services.recommendation import infer_category_semantic, recommend_categories


CATEGORY_KEYWORDS = {
    "Cat Food": ["cat food", "catfood", "kitten food", "feline food", "cat meal"],
    "Dog Food": ["dog food", "dogfood", "puppy food", "canine food", "dog meal"],
    "Bird Supplies": ["bird", "parrot", "avian", "bird food", "bird cage", "bird seed"],
    "Fish & Aquatics": ["fish", "aquarium", "aquatic", "tank", "fish food"],
    "Collars & Leads": ["collar", "leash", "lead", "harness"],
    "Pet Beds": ["bed", "sleep", "sleeping", "mattress"],
    "Pet Grooming": ["groom", "grooming", "shampoo", "brush", "comb"],
    "Pet Health": ["health", "medicine", "medication", "vitamin", "supplement", "flea", "tick"],
    "Pet Toys": ["toy", "play", "chew", "ball", "interactive toy"],
    "Small Animals": ["rabbit", "hamster", "guinea pig", "small animal", "bunny"],
}

PET_TERMS = {
    "kitten": ("cat", "kitten"),
    "cat": ("cat", None),
    "feline": ("cat", None),
    "puppy": ("dog", "puppy"),
    "dog": ("dog", None),
    "canine": ("dog", None),
    "bird": ("bird", None),
    "parrot": ("bird", None),
    "fish": ("fish", None),
    "aquarium": ("fish", None),
    "rabbit": ("small-animal", None),
    "hamster": ("small-animal", None),
    "guinea": ("small-animal", None),
    "small animal": ("small-animal", None),
}

LOCATIONS = [
    "Dhaka",
    "Mirpur",
    "Dhanmondi",
    "Uttara",
    "Banani",
    "Gulshan",
    "Bashundhara",
    "Mohammadpur",
    "Savar",
    "Gazipur",
    "Chattogram",
    "Sylhet",
    "Khulna",
    "Rajshahi",
]

KNOWN_BRANDS = [
    "Royal Canin",
    "Pedigree",
    "Whiskas",
    "Purina",
    "Orijen",
    "Acana",
    "Me-O",
    "Drools",
]

STOPWORDS = {
    "i",
    "need",
    "good",
    "for",
    "my",
    "a",
    "an",
    "the",
    "in",
    "at",
    "to",
    "of",
    "and",
    "under",
    "below",
    "less",
    "than",
    "within",
    "budget",
    "above",
    "more",
    "over",
    "bdt",
    "tk",
    "taka",
    "product",
    "products",
    "please",
    "show",
    "me",
    "want",
    "looking",
}

TYPO_MAP = {
    "kitn": "kitten",
    "fud": "food",
    "shampu": "shampoo",
    "medecine": "medicine",
    "leed": "lead",
    "collor": "collar",
}


def _normalize_query(text: str) -> str:
    q = text.lower().strip()
    q = q.replace("৳", " bdt ")
    q = re.sub(r"[^a-z0-9\s\-,.]", " ", q)
    q = re.sub(r"\s+", " ", q).strip()
    return q


def _extract_price(patterns: list[str], text: str) -> Optional[float]:
    for p in patterns:
        match = re.search(p, text, flags=re.IGNORECASE)
        if match:
            raw = match.group(1).replace(",", "")
            try:
                return float(raw)
            except ValueError:
                continue
    return None


def _fuzzy_fix_tokens(query: str) -> str:
    tokens = query.split()
    vocab = set()
    for terms in CATEGORY_KEYWORDS.values():
        for term in terms:
            vocab.update(term.split())
    vocab.update(PET_TERMS.keys())
    vocab.update(["food", "medicine", "shampoo", "collar", "leash", "toy", "bed"])

    fixed: list[str] = []
    for token in tokens:
        if token in TYPO_MAP:
            fixed.append(TYPO_MAP[token])
            continue
        if len(token) <= 2 or token.isdigit():
            fixed.append(token)
            continue
        if token in vocab:
            fixed.append(token)
            continue
        close = get_close_matches(token, list(vocab), n=1, cutoff=0.86)
        fixed.append(close[0] if close else token)
    return " ".join(fixed)


def _detect_pet_type(normalized_query: str) -> tuple[Optional[str], Optional[str]]:
    for term, (pet_type, age_group) in PET_TERMS.items():
        if term in normalized_query:
            return pet_type, age_group
    return None, None


def _detect_category(normalized_query: str, pet_type: Optional[str]) -> Optional[str]:
    for category, terms in CATEGORY_KEYWORDS.items():
        if any(term in normalized_query for term in terms):
            return category

    if "food" in normalized_query or "meal" in normalized_query:
        if pet_type == "cat":
            return "Cat Food"
        if pet_type == "dog":
            return "Dog Food"

    semantic_category = infer_category_semantic(normalized_query)
    return semantic_category


def _detect_location(normalized_query: str) -> Optional[str]:
    for location in LOCATIONS:
        if location.lower() in normalized_query:
            return location
    return None


def _detect_brand(raw_query: str) -> Optional[str]:
    for brand in KNOWN_BRANDS:
        if brand.lower() in raw_query.lower():
            return brand
    return None


def parse_query(query: str) -> dict:
    original_query = query.strip()
    normalized = _normalize_query(original_query)
    corrected_query = _fuzzy_fix_tokens(normalized)

    pet_type, age_group = _detect_pet_type(corrected_query)
    category = _detect_category(corrected_query, pet_type)

    max_price = _extract_price(
        [
            r"(?:under|below|less than|within|budget)\s*(\d+[\d,]*)",
            r"(?:bdt|tk|taka)\s*(\d+[\d,]*)",
            r"(\d+[\d,]*)\s*(?:bdt|tk|taka)",
        ],
        corrected_query,
    )
    min_price = _extract_price(
        [
            r"(?:above|more than|over)\s*(\d+[\d,]*)",
        ],
        corrected_query,
    )

    location = _detect_location(corrected_query)
    brand = _detect_brand(original_query)

    tokens = re.findall(r"[a-z]+", corrected_query)
    keywords = [token for token in tokens if token not in STOPWORDS and len(token) > 2]

    normalized_pet_type = "small_animal" if pet_type == "small-animal" else pet_type
    rec_pet_type = "small-animal" if normalized_pet_type == "small_animal" else normalized_pet_type

    return {
        "success": True,
        "query": original_query,
        "intent": "product_search",
        "pet_type": normalized_pet_type,
        "age_group": age_group,
        "category": category,
        "max_price": max_price,
        "min_price": min_price,
        "location": location,
        "brand": brand,
        "keywords": keywords[:12],
        "recommended_categories": recommend_categories(rec_pet_type, category),
    }
