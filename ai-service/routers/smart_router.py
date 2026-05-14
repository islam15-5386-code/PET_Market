from fastapi import APIRouter

from schemas.search_schema import SearchParseRequest, SearchParseResponse
from schemas.description_schema import DescriptionRequest
from schemas.chatbot_schema import ChatbotRequest
from schemas.smart_router_schema import SmartRouteRequest, SmartRouteResponse
from services.cache_key_builder import build_cache_key
from services.fallback_service import fallback_result
from services.intent_classifier import classify_intent
from services.llm_provider import generate_json
from services.prompt_builder import build_prompt
from services.rule_parser import parse_search_query
from services.safety_detector import detect_safety
from services.token_budget_manager import TokenBudgetManager

router = APIRouter(prefix="/ai", tags=["Smart AI Router"])
budget = TokenBudgetManager()
MEM_CACHE: dict[str, dict] = {}


@router.get("/health")
def health():
    return {"success": True, "status": "ok", "service": "smart-ai-router"}


@router.post("/route", response_model=SmartRouteResponse)
def route_ai(req: SmartRouteRequest):
    feature = req.feature
    payload = req.input or {}

    if feature == "product_search":
        query = str(payload.get("query", ""))
        parsed = parse_search_query(query)
        return SmartRouteResponse(
            feature=feature,
            strategy_used="rule_based",
            result=parsed,
            cost_saved_reason="Search parsed by rules/local model; no LLM call.",
        )

    if feature == "pet_chatbot":
        message = str(payload.get("message", ""))
        safety, vet = detect_safety(message)
        if safety == "emergency":
            return SmartRouteResponse(
                feature=feature,
                strategy_used="template",
                result={
                    "reply": vet,
                    "intent": "emergency_warning",
                    "safety_level": "emergency",
                    "vet_warning": vet,
                    "recommended_product_filters": {},
                },
                cost_saved_reason="Emergency safety template returned without LLM.",
            )

        intent, confidence = classify_intent(message)
        result = fallback_result("pet_chatbot", {"pet_type": payload.get("pet_type")})
        result.update({
            "intent": intent,
            "confidence": confidence,
            "safety_level": safety,
            "vet_warning": vet,
            "recommended_product_filters": parse_search_query(message),
        })
        return SmartRouteResponse(
            feature=feature,
            strategy_used="local_model" if confidence > 0 else "template",
            result=result,
            cost_saved_reason="Chatbot served via safety + local model/template.",
        )

    if feature == "product_description":
        key = build_cache_key(feature, payload)
        if key in MEM_CACHE:
            return SmartRouteResponse(
                feature=feature,
                strategy_used="cache",
                result=MEM_CACHE[key],
                cost_saved_reason="Cache hit; skipped LLM.",
            )

        fallback = fallback_result(feature, payload)
        prompt = build_prompt(DescriptionRequest(**{
            "product_name": payload.get("name") or payload.get("product_name") or "Pet Product",
            "category": payload.get("category") or "Pet Supplies",
            "pet_type": payload.get("pet_type") or "Pet",
            "age_group": payload.get("age_group"),
            "brand": payload.get("brand"),
            "price": payload.get("price"),
            "key_features": payload.get("features") or payload.get("key_features"),
            "language": payload.get("language") or "English",
            "tone": payload.get("tone") or "professional",
        }))

        llm = generate_json(prompt, feature, budget.allowed_output_tokens(feature))
        if llm.get("_skipped"):
            MEM_CACHE[key] = fallback
            return SmartRouteResponse(
                feature=feature,
                strategy_used="template",
                result=fallback,
                cost_saved_reason="AI_PROVIDER=none; using template fallback.",
            )

        merged = {**fallback, **llm}
        MEM_CACHE[key] = merged
        usage = merged.get("token_usage", {})
        return SmartRouteResponse(
            feature=feature,
            strategy_used="llm_fallback",
            result=merged,
            token_usage=usage,
            cost_saved_reason="LLM used only after cache miss.",
        )

    return SmartRouteResponse(
        feature=feature,
        strategy_used="template",
        result={"message": "Unsupported feature"},
        cost_saved_reason="No route matched.",
    )


@router.post("/product-search/parse", response_model=SearchParseResponse)
def parse_search(payload: SearchParseRequest):
    parsed = parse_search_query(payload.query)
    return SearchParseResponse(**parsed)


@router.post("/product-description/generate")
def generate_description(payload: dict):
    req = SmartRouteRequest(feature="product_description", input=payload)
    return route_ai(req)


@router.post("/pet-chatbot/message")
def chatbot_message(payload: ChatbotRequest):
    req = SmartRouteRequest(feature="pet_chatbot", input=payload.model_dump())
    return route_ai(req)


@router.post("/model/train")
def model_train():
    from services.model_trainer import train_chatbot_model

    result = train_chatbot_model()
    return {"success": True, "result": result}
