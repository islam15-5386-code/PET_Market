from fastapi import APIRouter, HTTPException

from schemas.embedding_schema import EmbeddingRequest, EmbeddingResponse
from services.embedding_service import create_embedding


router = APIRouter(prefix="/ai", tags=["AI Embeddings"])


@router.post("/embeddings", response_model=EmbeddingResponse)
def embed(payload: EmbeddingRequest):
    try:
        vector, provider, model = create_embedding(payload.text)
        return EmbeddingResponse(
            vector=vector,
            dimension=len(vector),
            provider=provider,
            model=model,
        )
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"embedding failed: {exc}")

