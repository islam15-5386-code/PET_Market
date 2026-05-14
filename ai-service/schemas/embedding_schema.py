from typing import List

from pydantic import BaseModel, Field


class EmbeddingRequest(BaseModel):
    text: str = Field(..., min_length=1, max_length=4000)


class EmbeddingResponse(BaseModel):
    vector: List[float]
    dimension: int
    provider: str
    model: str

