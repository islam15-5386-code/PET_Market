from pydantic import BaseModel, Field


class ChatbotRequest(BaseModel):
    message: str = Field(..., min_length=2, max_length=2000)
    pet_type: str | None = Field(default=None, max_length=50)
    locale: str | None = Field(default="Bangladesh", max_length=100)


class ChatbotResponse(BaseModel):
    success: bool
    reply: str | None = None
    model: str | None = None
    message: str | None = None
