from fastapi import APIRouter, HTTPException

from schemas.chatbot_schema import ChatbotRequest, ChatbotResponse
from services.chatbot_service import run_chatbot

router = APIRouter(prefix="/ai", tags=["Pet Chatbot"])


@router.post("/pet-chatbot", response_model=ChatbotResponse)
def pet_chatbot(payload: ChatbotRequest):
    result = run_chatbot(
        message=payload.message,
        pet_type=payload.pet_type,
        locale=payload.locale,
    )
    if not result.get("success"):
        message = result.get("message", "Chatbot request failed.")
        status_code = 500
        if "OPENAI_API_KEY is missing" in message:
            status_code = 503
        raise HTTPException(status_code=status_code, detail=message)
    return ChatbotResponse(**result)
