from fastapi import APIRouter

from schemas.product_search_schema import ProductSearchParseRequest, ProductSearchParseResponse
from services.query_parser import parse_query

router = APIRouter(prefix="/ai", tags=["AI Product Search"])


@router.post("/product-search/parse", response_model=ProductSearchParseResponse)
def product_search_parse(payload: ProductSearchParseRequest):
    parsed = parse_query(payload.query)
    return ProductSearchParseResponse(**parsed)


@router.post("/product-search", response_model=ProductSearchParseResponse)
def product_search_legacy(payload: ProductSearchParseRequest):
    parsed = parse_query(payload.query)
    return ProductSearchParseResponse(**parsed)
