import os
from typing import Optional

from dotenv import load_dotenv
from openai import OpenAI


class OpenAIServiceError(Exception):
    """Raised when OpenAI service cannot complete a request safely."""


load_dotenv()


class OpenAIService:
    def __init__(self) -> None:
        self.api_key = os.getenv("OPENAI_API_KEY", "").strip()
        self.model = os.getenv("OPENAI_MODEL", "gpt-4o-mini").strip()

    def _get_client(self) -> OpenAI:
        if not self.api_key or self.api_key == "your_api_key_here":
            raise OpenAIServiceError(
                "OPENAI_API_KEY is missing. Set it in ai-service/.env."
            )
        return OpenAI(api_key=self.api_key)

    def generate_text(
        self,
        *,
        system_prompt: str,
        user_prompt: str,
        temperature: float = 0.3,
        max_tokens: Optional[int] = 400,
    ) -> str:
        try:
            client = self._get_client()
            response = client.responses.create(
                model=self.model,
                temperature=temperature,
                max_output_tokens=max_tokens,
                input=[
                    {"role": "system", "content": system_prompt},
                    {"role": "user", "content": user_prompt},
                ],
            )
            output = (response.output_text or "").strip()
            if not output:
                raise OpenAIServiceError("OpenAI returned an empty response.")
            return output
        except OpenAIServiceError:
            raise
        except Exception as exc:  # noqa: BLE001
            raise OpenAIServiceError(f"OpenAI request failed: {exc}") from exc
