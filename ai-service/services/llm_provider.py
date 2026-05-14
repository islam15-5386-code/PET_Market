import os
from typing import Any

from services.provider_client import call_provider


def generate_json(prompt: str, feature: str, max_output_tokens: int) -> dict[str, Any]:
    provider = os.getenv("AI_PROVIDER", "none").strip().lower()
    if provider == "none":
        return {
            "provider_name": "none",
            "model_name": "none",
            "token_usage": {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0},
            "_skipped": True,
        }

    os.environ["MAX_OUTPUT_TOKENS"] = str(max_output_tokens)
    result = call_provider(prompt)
    result.setdefault("provider_name", provider)
    result.setdefault("model_name", os.getenv("OPENAI_MODEL") or os.getenv("GEMINI_MODEL") or os.getenv("CLAUDE_MODEL") or "unknown")
    result.setdefault("token_usage", {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0})
    return result
