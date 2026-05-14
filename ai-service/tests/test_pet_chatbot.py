from schemas.chatbot_schema import ChatbotRequest
from services.chatbot_service import run_chatbot


def test_food_query_detects_pet_and_category():
    out = run_chatbot(ChatbotRequest(message='Which food is good for my puppy?'))
    assert out['category'] == 'food'
    assert out['pet_type'] == 'dog'


def test_emergency_detection():
    out = run_chatbot(ChatbotRequest(message='My cat is bleeding and breathing problem'))
    assert out['safety_level'] == 'emergency'
    assert 'veterinarian immediately' in out['reply'].lower()


def test_bangla_mixed_query():
    out = run_chatbot(ChatbotRequest(message='amar biral khabar khacche na, ki korbo?'))
    assert out['intent'] in ['health_warning', 'emergency_warning']
    assert out['vet_warning'] is not None
