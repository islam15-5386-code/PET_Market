from typing import Literal

from pydantic import BaseModel, Field


class DescriptionRequest(BaseModel):
    product_name: str = Field(..., min_length=2, max_length=255)
    category: str = Field(..., min_length=2, max_length=120)
    pet_type: str = Field(..., min_length=2, max_length=50)
    age_group: str | None = Field(default=None, max_length=50)
    brand: str | None = Field(default=None, max_length=120)
    price: float | None = Field(default=None, ge=0)
    weight_or_size: str | None = Field(default=None, max_length=100)
    ingredients_or_materials: list[str] | str | None = None
    key_features: list[str] | str | None = None
    usage_instruction: str | None = Field(default=None, max_length=1000)
    safety_note: str | None = Field(default=None, max_length=1000)
    target_customer: str | None = Field(default=None, max_length=255)
    language: Literal["English", "Bangla", "Bangla-English mixed"] = "English"
    tone: Literal["professional", "friendly", "SEO optimized"] = "professional"


class TokenUsage(BaseModel):
    prompt_tokens: int = 0
    completion_tokens: int = 0
    total_tokens: int = 0


class DescriptionResponse(BaseModel):
    model_config = {"protected_namespaces": ()}

    professional_product_title: str
    short_description: str
    long_description: str
    seo_keywords: list[str]
    benefits: list[str]
    care_instruction: str
    usage_instruction: str
    safety_warning: str
    meta_title: str
    meta_description: str
    suggested_tags: list[str]
    provider_name: str
    model_name: str
    token_usage: TokenUsage
