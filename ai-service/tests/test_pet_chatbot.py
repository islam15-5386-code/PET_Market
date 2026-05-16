from fastapi.testclient import TestClient

from main import app


client = TestClient(app)


def test_chatbot_emergency_warning():
    res = client.post(
        "/ai/pet-chatbot/message",
        json={"message": "My dog is vomiting", "session_id": "abc123", "conversation_history": []},
    )
    assert res.status_code == 200
    data = res.json()
    assert data["intent"] == "emergency_warning"
    assert data["safety_level"] == "emergency"
    assert data["vet_warning"] is not None


def test_bangla_not_eating_warning():
    res = client.post(
        "/ai/pet-chatbot/message",
        json={"message": "amar biral khabar khacche na", "session_id": "abc123", "conversation_history": []},
    )
    assert res.status_code == 200
    data = res.json()
    assert data["pet_type"] == "cat"
    assert data["intent"] in ["health_warning", "emergency_warning"]
