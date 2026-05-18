import json
import os
import re
from typing import Any

PLACEHOLDER_KEYS = {
    "",
    "your_api_key_here",
    "your-openai-key",
    "your-gemini-key",
    "replace_me",
    "none",
    "null",
}


def _extract_json(text: str) -> dict[str, Any]:
    text = (text or "").strip()
    if not text:
        raise ValueError("empty provider output")
    match = re.search(r"\{.*\}", text, flags=re.DOTALL)
    if match:
        text = match.group(0)
    return json.loads(text)


def _safe_usage(resp_usage: Any) -> dict[str, int]:
    if not resp_usage:
        return {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0}
    return {
        "prompt_tokens": int(getattr(resp_usage, "prompt_tokens", 0) or 0),
        "completion_tokens": int(getattr(resp_usage, "completion_tokens", 0) or 0),
        "total_tokens": int(getattr(resp_usage, "total_tokens", 0) or 0),
    }


def call_provider(prompt: str) -> dict[str, Any]:
    provider = os.getenv("AI_PROVIDER", "none").strip().lower()
    openai_key = os.getenv("OPENAI_API_KEY", "").strip()
    gemini_key = os.getenv("GEMINI_API_KEY", "").strip()
    has_openai = openai_key.lower() not in PLACEHOLDER_KEYS
    has_gemini = gemini_key.lower() not in PLACEHOLDER_KEYS

    if provider == "auto":
        if has_gemini:
            provider = "gemini"
        elif has_openai:
            provider = "openai"
        else:
            provider = "none"

    if provider == "none":
        raise RuntimeError("provider disabled")
    if provider == "gemini" and not has_gemini:
        raise RuntimeError("GEMINI_API_KEY missing or placeholder")
    if provider == "openai" and not has_openai:
        raise RuntimeError("OPENAI_API_KEY missing or placeholder")

    last_error = None
    for _ in range(2):
        try:
            if provider == "openai":
                return _call_openai(prompt)
            if provider == "gemini":
                return _call_gemini(prompt)
            raise RuntimeError(f"unsupported provider: {provider}")
        except Exception as exc:
            last_error = exc
    raise RuntimeError(str(last_error) if last_error else "provider call failed")


def _call_openai(prompt: str) -> dict[str, Any]:
    from openai import OpenAI

    api_key = os.getenv("OPENAI_API_KEY", "")
    model = os.getenv("OPENAI_MODEL", "gpt-4o-mini")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY missing")

    client = OpenAI(api_key=api_key, timeout=float(os.getenv("AI_TIMEOUT_SECONDS", "12")))
    max_output_tokens = int(os.getenv("MAX_OUTPUT_TOKENS", "450"))
    resp = client.responses.create(model=model, input=prompt, max_output_tokens=max_output_tokens, temperature=0.2)
    payload = _extract_json(resp.output_text or "")
    payload["provider_name"] = "local_template_or_llm"
    payload["model_name"] = model
    payload["token_usage"] = _safe_usage(getattr(resp, "usage", None))
    return payload


def _call_gemini(prompt: str) -> dict[str, Any]:
    import google.generativeai as genai

    api_key = os.getenv("GEMINI_API_KEY", "")
    model = os.getenv("GEMINI_MODEL", "gemini-1.5-flash")
    if not api_key:
        raise RuntimeError("GEMINI_API_KEY missing")

    genai.configure(api_key=api_key)
    llm = genai.GenerativeModel(model)
    max_output_tokens = int(os.getenv("MAX_OUTPUT_TOKENS", "450"))
    resp = llm.generate_content(prompt, generation_config={"temperature": 0.2, "max_output_tokens": max_output_tokens})
    payload = _extract_json(getattr(resp, "text", "") or "")
    payload["provider_name"] = "local_template_or_llm"
    payload["model_name"] = model
    payload["token_usage"] = {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0}
    return payload
