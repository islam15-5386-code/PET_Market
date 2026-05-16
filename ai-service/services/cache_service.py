import hashlib
import json
from threading import Lock


class InMemoryCache:
    def __init__(self) -> None:
        self._store: dict[str, dict] = {}
        self._lock = Lock()

    def _key(self, payload: dict) -> str:
        raw = json.dumps(payload, sort_keys=True, ensure_ascii=True)
        return hashlib.sha256(raw.encode("utf-8")).hexdigest()

    def get(self, payload: dict) -> dict | None:
        key = self._key(payload)
        with self._lock:
            return self._store.get(key)

    def set(self, payload: dict, value: dict) -> None:
        key = self._key(payload)
        with self._lock:
            self._store[key] = value


cache_service = InMemoryCache()
