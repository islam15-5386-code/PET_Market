import hashlib
import json
from typing import Any


def build_cache_key(feature: str, payload: dict[str, Any]) -> str:
    stable = json.dumps(payload, ensure_ascii=True, sort_keys=True, separators=(",", ":"))
    digest = hashlib.sha256(f"{feature}:{stable}".encode("utf-8")).hexdigest()
    return f"{feature}:{digest}"
