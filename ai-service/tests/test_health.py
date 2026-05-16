from fastapi.testclient import TestClient

from main import app


def test_ai_health():
    client = TestClient(app)
    response = client.get("/ai/health")
    assert response.status_code == 200
    assert response.json() == {
        "status": "ok",
        "service": "pet-marketplace-ai",
        "version": "1.0.0",
    }
