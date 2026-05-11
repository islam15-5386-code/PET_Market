from fastapi import APIRouter, HTTPException

from schemas.product_search_schema import ProductSearchRequest, ProductSearchAIResponse
from services.query_parser import parse_query


router = APIRouter(prefix="/ai", tags=["AI Product Search"])


@router.post("/product-search", response_model=ProductSearchAIResponse)
def product_search(payload: ProductSearchRequest):
    try:
        parsed = parse_query(payload.query)
        return ProductSearchAIResponse(**parsed)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"AI parsing failed: {exc}")
