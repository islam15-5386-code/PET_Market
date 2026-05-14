# API Alignment (Frontend ↔ Laravel ↔ FastAPI)

## Base URLs
- Frontend API base: `NEXT_PUBLIC_API_URL` (expected: `http://localhost:8000/api`)
- Laravel API base: `http://localhost:8000/api`
- FastAPI AI base (Laravel internal call): `AI_SERVICE_URL` (expected: `http://localhost:8001`)

## 1) Smart Product Search

### Frontend -> Laravel
- `POST /api/ai-search`
- Request:
```json
{ "query": "kitten food under 1000 bdt" }
```

### Laravel -> FastAPI
- `POST /ai/product-search`
- Request:
```json
{ "query": "kitten food under 1000 bdt", "user_id": 1 }
```

### FastAPI -> Laravel (parsed filters)
```json
{
  "success": true,
  "query": "kitten food under 1000 bdt",
  "intent": "product_search",
  "pet_type": "cat",
  "category": "food",
  "age_group": "kitten",
  "price_min": null,
  "price_max": 1000,
  "min_price": null,
  "max_price": 1000,
  "keywords": ["kitten", "food"],
  "confidence": 0.85
}
```

### Laravel -> Frontend
```json
{
  "success": true,
  "data": {
    "query": "kitten food under 1000 bdt",
    "ai_filters": { "pet_type": "cat", "category": "food", "semantic_applied": true },
    "products": []
  }
}
```

## 2) AI Product Description Generator

### Frontend -> Laravel
- `POST /api/ai/product-description/generate` (auth required)

### Laravel -> FastAPI
- `POST /ai/product-description/generate`

### FastAPI -> Laravel/Frontend
```json
{
  "professional_product_title": "...",
  "short_description": "...",
  "long_description": "...",
  "seo_keywords": ["..."],
  "benefits": ["..."],
  "care_instruction": "...",
  "usage_instruction": "...",
  "safety_warning": "Consult a veterinarian for medical concerns.",
  "meta_title": "...",
  "meta_description": "...",
  "suggested_tags": ["..."],
  "provider_name": "openai|gemini|fallback",
  "model_name": "...",
  "token_usage": {
    "prompt_tokens": 0,
    "completion_tokens": 0,
    "total_tokens": 0
  }
}
```

## 3) AI Pet Care Chatbot

### Frontend -> Laravel
- `POST /api/chatbot/message`
```json
{ "message": "my cat is not eating", "session_id": "uuid" }
```

### Laravel -> FastAPI
- `POST /chatbot/message`
```json
{
  "message": "my cat is not eating",
  "session_id": "uuid",
  "user_id": 1,
  "conversation_history": []
}
```

### Laravel -> Frontend
```json
{
  "success": true,
  "data": {
    "session_id": "uuid",
    "reply": "This may be serious. Please contact a veterinarian immediately.",
    "intent": "emergency_warning",
    "safety_level": "emergency",
    "vet_warning": "This may be serious. Please contact a veterinarian immediately.",
    "recommended_products": []
  }
}
```

## 4) Product APIs
- `GET /api/products`
- `GET /api/products/{slug}`

Product shape includes:
- `image_url`, `thumbnail_url`, `images`
- `pet_type`, `sub_category`, `category_type`
- `ai_generated_*` fields (where available in admin/detail workflows)

## 5) Error Format
Laravel API error envelope:
```json
{
  "success": false,
  "message": "Validation error or service error"
}
```

## 6) Fallback Rules
- AI Search failure: return Laravel error with safe message; frontend should show fallback notice.
- Description generation failure: Laravel uses template fallback and still returns structured output.
- Chatbot AI failure: Laravel service fallback reply + safe vet warning for risky health terms.
