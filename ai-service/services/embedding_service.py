import os
from threading import Lock
from typing import List

import numpy as np
from openai import OpenAI
from sklearn.feature_extraction.text import HashingVectorizer
from sklearn.preprocessing import normalize


_EMBEDDER = None
_EMBEDDER_LOCK = Lock()
_HASHING = HashingVectorizer(
    n_features=384,
    alternate_sign=False,
    ngram_range=(1, 2),
    norm=None,
)


def _normalize(vector: List[float], dimension: int) -> List[float]:
    if len(vector) == dimension:
        return vector
    if len(vector) > dimension:
        return vector[:dimension]
    return vector + ([0.0] * (dimension - len(vector)))


def _local_embedding(text: str) -> tuple[list[float], str]:
    global _EMBEDDER
    model_name = os.getenv("AI_EMBEDDING_MODEL", "sentence-transformers/all-MiniLM-L6-v2")
    try:
        from sentence_transformers import SentenceTransformer

        with _EMBEDDER_LOCK:
            if _EMBEDDER is None:
                _EMBEDDER = SentenceTransformer(model_name)
        vector = _EMBEDDER.encode(text, normalize_embeddings=True).tolist()
        return _normalize(vector, 384), model_name
    except Exception:
        # Lightweight local fallback for environments without torch/sentence-transformers.
        sparse_vec = _HASHING.transform([text])
        dense = sparse_vec.toarray().astype(np.float32)
        normed = normalize(dense, norm="l2")[0]
        return normed.tolist(), "sklearn-hashing-384"


def _openai_embedding(text: str) -> tuple[list[float], str]:
    model = os.getenv("OPENAI_EMBEDDING_MODEL", "text-embedding-3-small")
    api_key = os.getenv("OPENAI_API_KEY", "")
    client = OpenAI(api_key=api_key)
    result = client.embeddings.create(
        model=model,
        input=text,
        dimensions=384,
    )
    vector = result.data[0].embedding
    return _normalize(vector, 384), model


def create_embedding(text: str) -> tuple[list[float], str, str]:
    provider = os.getenv("AI_EMBEDDING_PROVIDER", "sentence_transformers").lower()
    if provider == "openai" and os.getenv("OPENAI_API_KEY"):
        vector, model = _openai_embedding(text)
        return vector, "openai", model

    vector, model = _local_embedding(text)
    return vector, "sentence_transformers", model
