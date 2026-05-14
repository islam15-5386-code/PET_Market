from fastapi import APIRouter, HTTPException

from schemas.description_schema import DescriptionRequest, DescriptionResponse
from services.description_service import generate_description

router = APIRouter(tags=["Description Generator"])


@router.post("/ai/product-description/generate", response_model=DescriptionResponse)
def generate_product_description(payload: DescriptionRequest):
    try:
        result = generate_description(payload)
        return DescriptionResponse(**result)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"Description generation failed: {exc}") from exc


# Backward-compatible route
@router.post("/api/ai/generate-description", response_model=DescriptionResponse)
def generate_product_description_legacy(payload: DescriptionRequest):
    return generate_product_description(payload)
