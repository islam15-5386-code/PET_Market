import os
from functools import lru_cache
from typing import Optional


ALL_CATEGORIES = [
    "Bird Supplies",
    "Cat Food",
    "Collars & Leads",
    "Dog Food",
    "Fish & Aquatics",
    "Pet Beds",
    "Pet Grooming",
    "Pet Health",
    "Pet Toys",
    "Small Animals",
]


def recommend_categories(pet_type: Optional[str], category: Optional[str]) -> list[str]:
    if category:
        base = [category]
    else:
        base = []

    mapping = {
        "cat": ["Cat Food", "Pet Health", "Pet Toys", "Pet Grooming"],
        "dog": ["Dog Food", "Collars & Leads", "Pet Beds", "Pet Toys"],
        "bird": ["Bird Supplies", "Pet Health"],
        "fish": ["Fish & Aquatics", "Pet Health"],
        "small-animal": ["Small Animals", "Pet Health", "Pet Beds"],
    }

    if pet_type and pet_type in mapping:
        base.extend(mapping[pet_type])

    seen: set[str] = set()
    out: list[str] = []
    for item in base:
        if item and item not in seen:
            seen.add(item)
            out.append(item)

    return out[:5]


def infer_category_semantic(query: str) -> Optional[str]:
    """Optional lightweight semantic inference using MiniLM if enabled."""
    use_minilm = os.getenv("AI_USE_MINILM", "false").strip().lower() == "true"
    if not use_minilm:
        return None

    model = _load_embedding_model()
    if model is None:
        return None

    try:
        query_vec = model.encode([query], normalize_embeddings=True)
        cat_vecs = model.encode(_category_prompts(), normalize_embeddings=True)
        scores = (cat_vecs @ query_vec[0]).tolist()
        best_idx = max(range(len(scores)), key=lambda i: scores[i])
        best_score = scores[best_idx]
        if best_score < 0.35:
            return None
        return ALL_CATEGORIES[best_idx]
    except Exception:
        return None


@lru_cache(maxsize=1)
def _load_embedding_model():
    try:
        from sentence_transformers import SentenceTransformer  # type: ignore

        model_name = os.getenv("AI_MINILM_MODEL", "sentence-transformers/all-MiniLM-L6-v2")
        return SentenceTransformer(model_name)
    except Exception:
        return None


@lru_cache(maxsize=1)
def _category_prompts() -> tuple[str, ...]:
    return (
        "bird supplies food cage parrot canary accessories",
        "cat food kitten dry food wet food feline nutrition",
        "pet collar dog leash lead harness walking",
        "dog food puppy dry food wet food canine nutrition",
        "fish aquarium filter tank aquatics fish food",
        "pet bed dog bed cat bed sleeping mattress",
        "pet grooming shampoo brush comb anti shedding care",
        "pet health medicine vitamin flea tick supplement care",
        "pet toys chew toy cat ball rope play",
        "small animal rabbit hamster guinea pig hay supplies",
    )
