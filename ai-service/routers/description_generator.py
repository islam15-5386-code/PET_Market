from fastapi import APIRouter, HTTPException

from schemas.description_schema import DescriptionRequest, DescriptionResponse
from services.description_service import generate_description

router = APIRouter(tags=["Description Generator"])


@router.post("/api/ai/generate-description", response_model=DescriptionResponse)
def generate_ai_description(payload: DescriptionRequest):
    try:
        result = generate_description(payload)
        return DescriptionResponse(**result)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"Description generation failed: {exc}") from exc


# Backward-compatible route
@router.post("/ai/description-generator", response_model=DescriptionResponse)
def description_generator_legacy(payload: DescriptionRequest):
    return generate_ai_description(payload)
