from pydantic import BaseModel, Field


class DescriptionRequest(BaseModel):
    name: str = Field(..., min_length=2, max_length=180)
    category: str | None = Field(default=None, max_length=120)
    pet_type: str | None = Field(default=None, max_length=50)
    age_group: str | None = Field(default=None, max_length=50)
    price: float | None = Field(default=None, ge=0)
    brand: str | None = Field(default=None, max_length=120)
    features: list[str] = Field(default_factory=list, max_length=8)
    stock: int | None = Field(default=None, ge=0)
    target_location: str = Field(default="Bangladesh", max_length=120)


class DescriptionResponse(BaseModel):
    source: str
    title: str
    description: str
    seo_keywords: list[str]
    benefits: list[str]
