import os
import sys
import unittest
from pathlib import Path

from fastapi.testclient import TestClient

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from main import app  # noqa: E402


class DescriptionGeneratorTest(unittest.TestCase):
    def test_template_fallback_when_no_provider_keys(self):
        os.environ["AI_PROVIDER"] = "auto"
        os.environ["OPENAI_API_KEY"] = ""
        os.environ["GEMINI_API_KEY"] = ""
        os.environ["OLLAMA_BASE_URL"] = "http://127.0.0.1:9999"

        client = TestClient(app)
        response = client.post(
            "/api/ai/generate-description",
            json={
                "name": "Kitten Dry Food",
                "category": "Cat Food",
                "pet_type": "Cat",
                "age_group": "Kitten",
                "price": 750,
                "brand": "Meow Mix",
                "features": ["healthy growth", "easy digestion", "high protein"],
                "stock": 25,
                "target_location": "Bangladesh",
            },
        )
        self.assertEqual(response.status_code, 200)
        payload = response.json()
        self.assertEqual(payload["source"], "template_fallback")
        self.assertTrue(isinstance(payload["title"], str) and payload["title"])
        self.assertTrue(isinstance(payload["description"], str) and payload["description"])
        self.assertEqual(len(payload["seo_keywords"]), 5)
        self.assertEqual(len(payload["benefits"]), 3)


if __name__ == "__main__":
    unittest.main()
