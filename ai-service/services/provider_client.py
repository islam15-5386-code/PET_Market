import json
import os
import re
from typing import Any


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
    provider = os.getenv("AI_PROVIDER", "openai").strip().lower()

    if provider == "openai":
        return _call_openai(prompt)
    if provider == "gemini":
        return _call_gemini(prompt)
    if provider == "claude":
        return _call_claude(prompt)
    if provider == "llama":
        return _call_llama(prompt)

    return _call_openai(prompt)


def _call_openai(prompt: str) -> dict[str, Any]:
    from openai import OpenAI

    api_key = os.getenv("OPENAI_API_KEY", "")
    model = os.getenv("OPENAI_MODEL", "gpt-4o-mini")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY missing")

    client = OpenAI(api_key=api_key)
    resp = client.responses.create(
        model=model,
        input=prompt,
        max_output_tokens=600,
        temperature=0.3,
    )
    payload = _extract_json(resp.output_text or "")
    payload["provider_name"] = "openai"
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
    resp = llm.generate_content(prompt, generation_config={"temperature": 0.3, "max_output_tokens": 600})
    payload = _extract_json(getattr(resp, "text", "") or "")
    payload["provider_name"] = "gemini"
    payload["model_name"] = model
    payload["token_usage"] = {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0}
    return payload


def _call_claude(prompt: str) -> dict[str, Any]:
    try:
        from anthropic import Anthropic
    except Exception as exc:
        raise RuntimeError(f"anthropic package missing: {exc}")

    api_key = os.getenv("ANTHROPIC_API_KEY", "")
    model = os.getenv("CLAUDE_MODEL", "claude-3-5-haiku-latest")
    if not api_key:
        raise RuntimeError("ANTHROPIC_API_KEY missing")

    client = Anthropic(api_key=api_key)
    resp = client.messages.create(
        model=model,
        max_tokens=700,
        temperature=0.3,
        messages=[{"role": "user", "content": prompt}],
    )

    text = ""
    for block in resp.content:
        text += getattr(block, "text", "")

    payload = _extract_json(text)
    payload["provider_name"] = "claude"
    payload["model_name"] = model
    payload["token_usage"] = {
        "prompt_tokens": int(getattr(resp.usage, "input_tokens", 0) or 0),
        "completion_tokens": int(getattr(resp.usage, "output_tokens", 0) or 0),
        "total_tokens": int((getattr(resp.usage, "input_tokens", 0) or 0) + (getattr(resp.usage, "output_tokens", 0) or 0)),
    }
    return payload


def _call_llama(prompt: str) -> dict[str, Any]:
    import requests

    base = os.getenv("LLAMA_BASE_URL", "http://127.0.0.1:11434")
    model = os.getenv("LLAMA_MODEL", "llama3.1:8b")
    resp = requests.post(
        f"{base.rstrip('/')}/api/generate",
        json={"model": model, "prompt": prompt, "stream": False},
        timeout=30,
    )
    resp.raise_for_status()
    text = resp.json().get("response", "")
    payload = _extract_json(text)
    payload["provider_name"] = "llama"
    payload["model_name"] = model
    payload["token_usage"] = {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0}
    return payload
