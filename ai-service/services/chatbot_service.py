from services.openai_service import OpenAIService, OpenAIServiceError

openai_service = OpenAIService()


def run_chatbot(message: str, pet_type: str | None = None, locale: str | None = "Bangladesh"):
    pet_context = f"Pet type: {pet_type}. " if pet_type else ""
    system_prompt = (
        "You are a safe, practical pet care assistant. "
        "Give friendly guidance for everyday pet care and suggest seeing a vet for emergencies."
    )
    user_prompt = (
        f"{pet_context}User locale: {locale or 'Bangladesh'}. "
        f"User question: {message}\n"
        "Keep response under 180 words. If symptoms seem severe, mention veterinarian consultation."
    )

    try:
        reply = openai_service.generate_text(
            system_prompt=system_prompt,
            user_prompt=user_prompt,
            temperature=0.4,
            max_tokens=260,
        )
        return {
            "success": True,
            "reply": reply,
            "model": openai_service.model,
            "message": "Chatbot response generated successfully.",
        }
    except OpenAIServiceError as exc:
        return {"success": False, "reply": None, "model": None, "message": str(exc)}
