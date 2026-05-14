from typing import Any, Literal

from pydantic import BaseModel, Field

FeatureType = Literal["product_search", "product_description", "pet_chatbot"]
StrategyType = Literal["rule_based", "cache", "local_model", "template", "llm_fallback"]


class SmartRouteRequest(BaseModel):
    feature: FeatureType
    input: dict[str, Any] = Field(default_factory=dict)
    user_id: int | None = None
    session_id: str | None = None


class TokenUsage(BaseModel):
    prompt_tokens: int = 0
    completion_tokens: int = 0
    total_tokens: int = 0


class SmartRouteResponse(BaseModel):
    feature: FeatureType
    strategy_used: StrategyType
    result: dict[str, Any] = Field(default_factory=dict)
    token_usage: TokenUsage = Field(default_factory=TokenUsage)
    cost_saved_reason: str = ""
