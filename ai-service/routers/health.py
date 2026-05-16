from fastapi import APIRouter
import os

router = APIRouter(prefix="/ai", tags=["Health"])


@router.get("/health")
def ai_health():
    return {"status": "ok", "service": os.getenv("APP_NAME", "pet-ai-service"), "version": "1.0.0"}
