from pydantic import BaseModel, Field
from typing import List, Optional


class ProductSearchRequest(BaseModel):
    query: str = Field(..., min_length=2, max_length=500)
    user_id: Optional[int] = None


class ProductSearchAIResponse(BaseModel):
    success: bool = True
    query: str
    intent: str = "product_search"
    pet_type: Optional[str] = None
    age_group: Optional[str] = None
    category: Optional[str] = None
    max_price: Optional[float] = None
    min_price: Optional[float] = None
    location: Optional[str] = None
    brand: Optional[str] = None
    keywords: List[str] = []
    recommended_categories: List[str] = []
