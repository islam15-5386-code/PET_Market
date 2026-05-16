from fastapi import APIRouter

from schemas.chatbot_schema import ChatbotRequest, ChatbotResponse
from services.chatbot_service import run_chatbot

router = APIRouter(prefix="/ai", tags=["Pet Chatbot"])


@router.post("/pet-chatbot/message", response_model=ChatbotResponse)
def chatbot_message(payload: ChatbotRequest):
    return run_chatbot(payload)
