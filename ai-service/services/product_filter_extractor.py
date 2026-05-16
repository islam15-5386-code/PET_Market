import re

PET_PATTERNS = {
    'cat': ['cat', 'kitten', 'biral', 'বিড়াল', 'বিড়াল'],
    'dog': ['dog', 'puppy', 'kukur', 'কুকুর'],
    'bird': ['bird', 'pakhi', 'পাখি', 'parrot'],
    'fish': ['fish', 'mach', 'মাছ', 'aquarium'],
    'rabbit': ['rabbit', 'bunny', 'khorgosh', 'খরগোশ'],
}

CATEGORY_PATTERNS = {
    'food': ['food', 'feed', 'খাবার'],
    'grooming': ['groom', 'shampoo', 'brush'],
    'medicine': ['medicine', 'med', 'fever', 'diarrhea'],
    'toy': ['toy', 'play', 'ball'],
    'accessory': ['collar', 'leash', 'harness', 'bed'],
    'cage': ['cage', 'crate'],
}

AGE_PATTERNS = {
    'kitten': ['kitten'],
    'puppy': ['puppy'],
    'adult': ['adult'],
    'senior': ['senior', 'old'],
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


def _detect_map(text: str, mapping: dict[str, list[str]]):
    for k, terms in mapping.items():
        if any(t in text for t in terms):
            return k
    return None


def extract_filters(message: str):
    text = message.lower().strip()

    pet_type = _detect_map(text, PET_PATTERNS)
    category = _detect_map(text, CATEGORY_PATTERNS)
    age_group = _detect_map(text, AGE_PATTERNS)
    location = _detect_map(text, LOCATION_PATTERNS)

    price_max = None
    price_min = None

    max_patterns = [r'(?:under|below|within)\s*(\d+[\d,]*)', r'(\d+[\d,]*)\s*(?:bdt|tk|taka)', r'(\d+[\d,]*)\s*টাকার']
    min_patterns = [r'(?:over|above|more than)\s*(\d+[\d,]*)']

    for p in max_patterns:
        m = re.search(p, text)
        if m:
            price_max = float(m.group(1).replace(',', ''))
            break

    for p in min_patterns:
        m = re.search(p, text)
        if m:
            price_min = float(m.group(1).replace(',', ''))
            break

    return {
        'pet_type': pet_type,
        'category': category,
        'age_group': age_group,
        'location': location,
        'price_min': price_min,
        'price_max': price_max,
    }
