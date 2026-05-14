from fastapi import APIRouter

from schemas.chatbot_schema import ChatbotRequest, ChatbotResponse
from services.chatbot_service import run_chatbot
from services.model_trainer import train_chatbot_model
from services.model_version_logger import log_model_version

router = APIRouter(tags=['Pet Chatbot'])


@router.post('/chatbot/message', response_model=ChatbotResponse)
def chatbot_message(payload: ChatbotRequest):
    return run_chatbot(payload)


@router.post('/chatbot/train')
def chatbot_train():
    result = train_chatbot_model()
    result['model_version_logged'] = log_model_version(result, status='trained')
    return result


@router.get('/chatbot/health')
def chatbot_health():
    return {'status': 'ok', 'service': 'pet-chatbot'}


# Backward compat
@router.post('/ai/pet-chatbot', response_model=ChatbotResponse)
def chatbot_message_legacy(payload: ChatbotRequest):
    return run_chatbot(payload)
