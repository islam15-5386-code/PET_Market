from typing import Literal

from pydantic import BaseModel, Field


class HistoryItem(BaseModel):
    sender: Literal['user', 'ai']
    message: str


class ChatbotRequest(BaseModel):
    message: str = Field(..., min_length=2, max_length=2000)
    session_id: str | None = Field(default=None, max_length=100)
    user_id: int | None = None
    conversation_history: list[HistoryItem] = []


class ChatbotResponse(BaseModel):
    reply: str
    intent: str
    pet_type: str | None = None
    category: str | None = None
    age_group: str | None = None
    price_min: float | None = None
    price_max: float | None = None
    safety_level: str
    vet_warning: str | None = None
    recommended_product_filters: dict
    confidence: float
