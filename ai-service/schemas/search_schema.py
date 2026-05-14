from typing import List, Optional

from pydantic import BaseModel, Field


class SearchParseRequest(BaseModel):
    query: str = Field(..., min_length=2, max_length=500)


class SearchParseResponse(BaseModel):
    intent: str
    pet_type: Optional[str] = None
    age_group: Optional[str] = None
    category: Optional[str] = None
    brand: Optional[str] = None
    location: Optional[str] = None
    breed: Optional[str] = None
    price_min: Optional[float] = None
    price_max: Optional[float] = None
    keywords: List[str] = []
    confidence: float
