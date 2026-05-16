from typing import Optional

from pydantic import BaseModel, Field


class ProductSearchParseRequest(BaseModel):
    query: str = Field(..., min_length=2, max_length=500)


class ProductSearchParseResponse(BaseModel):
    intent: str = "product_search"
    category: Optional[str] = None
    pet_type: Optional[str] = None
    age_group: Optional[str] = None
    location: Optional[str] = None
    price_min: Optional[float] = None
    price_max: Optional[float] = None
    keywords: list[str] = []
    confidence: float = 0.0
