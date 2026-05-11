import hashlib
import json
import os
import re
from typing import Any
from urllib import error, request

from dotenv import load_dotenv
from openai import OpenAI

from schemas.description_schema import DescriptionRequest

load_dotenv()

_CACHE: dict[str, dict[str, Any]] = {}


def generate_description(payload: DescriptionRequest) -> dict[str, Any]:
    compact = _compact_product_data(payload)
    prompt_hash = _hash_payload(compact)

    if prompt_hash in _CACHE:
        return _CACHE[prompt_hash]

    provider = os.getenv("AI_PROVIDER", "auto").strip().lower()
    result = _generate_by_provider(provider, compact)

    normalized = _normalize_output(result)
    _CACHE[prompt_hash] = normalized
    return normalized


def _generate_by_provider(provider: str, compact: dict[str, Any]) -> dict[str, Any]:
    if provider == "openai":
        return _safe_generate(generate_with_openai, compact) or generate_with_template(compact)
    if provider == "gemini":
        return _safe_generate(generate_with_gemini, compact) or generate_with_template(compact)
    if provider == "ollama":
        return _safe_generate(generate_with_ollama, compact) or generate_with_template(compact)
    if provider == "template":
        return generate_with_template(compact)

    # auto mode: OpenAI -> Gemini -> Ollama -> Template fallback
    return (
        _safe_generate(generate_with_openai, compact)
        or _safe_generate(generate_with_gemini, compact)
        or _safe_generate(generate_with_ollama, compact)
        or generate_with_template(compact)
    )


def generate_with_openai(compact: dict[str, Any]) -> dict[str, Any]:
    api_key = os.getenv("OPENAI_API_KEY", "").strip()
    model = os.getenv("OPENAI_MODEL", "gpt-4o-mini").strip()
    if not api_key or api_key == "your_api_key_here":
        raise RuntimeError("OPENAI_API_KEY missing")

    client = OpenAI(api_key=api_key)
    content = _build_prompt(compact)
    response = client.responses.create(
        model=model,
        input=content,
        temperature=0.4,
        max_output_tokens=220,
    )
    parsed = _extract_json(response.output_text or "")
    parsed["source"] = "ai_model"
    return parsed


def generate_with_gemini(compact: dict[str, Any]) -> dict[str, Any]:
    api_key = os.getenv("GEMINI_API_KEY", "").strip()
    model = os.getenv("GEMINI_MODEL", "gemini-1.5-flash").strip()
    if not api_key:
        raise RuntimeError("GEMINI_API_KEY missing")

    try:
        import google.generativeai as genai  # type: ignore
    except Exception as exc:  # noqa: BLE001
        raise RuntimeError(f"Gemini package missing: {exc}") from exc

    genai.configure(api_key=api_key)
    llm = genai.GenerativeModel(model)
    out = llm.generate_content(
        _build_prompt(compact),
        generation_config={"temperature": 0.4, "max_output_tokens": 220},
    )
    text = getattr(out, "text", "") or ""
    parsed = _extract_json(text)
    parsed["source"] = "ai_model"
    return parsed


def generate_with_ollama(compact: dict[str, Any]) -> dict[str, Any]:
    model = os.getenv("OLLAMA_MODEL", "phi3").strip()
    base_url = os.getenv("OLLAMA_BASE_URL", "http://127.0.0.1:11434").strip().rstrip("/")
    endpoint = f"{base_url}/api/generate"

    body = {
        "model": model,
        "prompt": _build_prompt(compact),
        "stream": False,
        "options": {"temperature": 0.4, "num_predict": 220},
    }
    req = request.Request(
        endpoint,
        data=json.dumps(body).encode("utf-8"),
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with request.urlopen(req, timeout=15) as res:
            raw = res.read().decode("utf-8")
        payload = json.loads(raw)
        text = payload.get("response", "")
        parsed = _extract_json(text)
        parsed["source"] = "ai_model"
        return parsed
    except error.URLError as exc:
        raise RuntimeError(f"Ollama unavailable: {exc}") from exc


def generate_with_template(compact: dict[str, Any]) -> dict[str, Any]:
    name = compact.get("name", "Pet Product")
    brand = compact.get("brand")
    category = compact.get("category", "Pet Supplies")
    pet_type = compact.get("pet_type", "pets")
    age_group = compact.get("age_group")
    price = compact.get("price")
    location = compact.get("target_location", "Bangladesh")
    features = compact.get("features", [])[:3]

    title_parts = [brand, name] if brand else [name]
    if age_group:
        title_parts.append(f"for {age_group}")
    title = " ".join([p for p in title_parts if p]).strip()

    feature_line = ", ".join(features) if features else f"reliable {category.lower()} quality"
    description = (
        f"{title} is a quality {category.lower()} option for {pet_type.lower()} owners in {location}. "
        f"It supports daily care with {feature_line}. "
        f"Great value"
    )
    if price:
        description += f" at around BDT {int(price)}."
    else:
        description += "."

    keywords = _dedupe_keywords(
        [
            f"{name} {category}".lower(),
            f"{pet_type} {category}".lower(),
            f"{brand or 'pet'} {category}".lower(),
            f"pet supplies {location}".lower(),
            f"{category} bangladesh".lower(),
        ]
    )[:5]

    benefits = [
        "Supports everyday pet care needs",
        "Balanced quality and budget-friendly value",
        "Suitable for Bangladesh pet marketplace buyers",
    ]
    return {
        "source": "template_fallback",
        "title": title[:140],
        "description": _trim_words(description, 70),
        "seo_keywords": keywords,
        "benefits": benefits,
    }


def _safe_generate(fn, compact: dict[str, Any]) -> dict[str, Any] | None:
    try:
        return fn(compact)
    except Exception:
        return None


def _build_prompt(compact: dict[str, Any]) -> str:
    return (
        "Generate JSON only: title, description under 70 words, 5 seo_keywords, 3 benefits. "
        f"Product: {json.dumps(compact, ensure_ascii=True)} "
        "Rules: simple English, Bangladesh pet marketplace, no false medical claims."
    )


def _extract_json(raw_text: str) -> dict[str, Any]:
    text = raw_text.strip()
    if not text:
        raise RuntimeError("Empty model output")

    fenced_match = re.search(r"```(?:json)?\s*(\{.*?\})\s*```", text, flags=re.DOTALL)
    if fenced_match:
        text = fenced_match.group(1).strip()

    if not text.startswith("{"):
        brace_match = re.search(r"\{.*\}", text, flags=re.DOTALL)
        if brace_match:
            text = brace_match.group(0)

    return json.loads(text)


def _normalize_output(result: dict[str, Any]) -> dict[str, Any]:
    title = str(result.get("title", "")).strip()[:140]
    description = _trim_words(str(result.get("description", "")).strip(), 70)
    seo_keywords = _dedupe_keywords(result.get("seo_keywords", []))[:5]
    benefits = _normalize_benefits(result.get("benefits", []))[:3]
    source = str(result.get("source", "template_fallback"))

    if not title or not description or len(seo_keywords) < 3 or len(benefits) < 2:
        fallback = generate_with_template(
            {
                "name": title or "Pet Product",
                "category": seo_keywords[0] if seo_keywords else "Pet Supplies",
                "target_location": "Bangladesh",
            }
        )
        return fallback

    return {
        "source": source,
        "title": title,
        "description": description,
        "seo_keywords": seo_keywords,
        "benefits": benefits,
    }


def _normalize_benefits(value: Any) -> list[str]:
    if isinstance(value, str):
        value = [v.strip("- ").strip() for v in value.split("\n") if v.strip()]
    if not isinstance(value, list):
        return []
    return [str(v).strip() for v in value if str(v).strip()]


def _dedupe_keywords(value: Any) -> list[str]:
    if isinstance(value, str):
        value = [v.strip() for v in value.split(",") if v.strip()]
    if not isinstance(value, list):
        return []
    seen: set[str] = set()
    out: list[str] = []
    for keyword in value:
        k = str(keyword).strip().lower()
        if not k or k in seen:
            continue
        seen.add(k)
        out.append(k)
    return out


def _compact_product_data(payload: DescriptionRequest) -> dict[str, Any]:
    compact = {
        "name": payload.name,
        "category": payload.category,
        "pet_type": payload.pet_type,
        "age_group": payload.age_group,
        "price": payload.price,
        "brand": payload.brand,
        "features": payload.features[:5],
        "stock": payload.stock,
        "target_location": payload.target_location or "Bangladesh",
    }
    return {k: v for k, v in compact.items() if v not in (None, "", [], {})}


def _hash_payload(compact: dict[str, Any]) -> str:
    return hashlib.sha256(json.dumps(compact, sort_keys=True).encode("utf-8")).hexdigest()


def _trim_words(text: str, max_words: int) -> str:
    words = text.split()
    if len(words) <= max_words:
        return text
    return " ".join(words[:max_words]).rstrip(".") + "."
