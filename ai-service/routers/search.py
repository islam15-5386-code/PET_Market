from fastapi import APIRouter, HTTPException

from schemas.search_schema import SearchParseRequest, SearchParseResponse
from services.query_parser import parse_query

router = APIRouter(prefix="/search", tags=["AI Search"])


@router.post("/parse", response_model=SearchParseResponse)
def parse_search(payload: SearchParseRequest):
    try:
        return parse_query(payload.query)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"search parse failed: {exc}")
