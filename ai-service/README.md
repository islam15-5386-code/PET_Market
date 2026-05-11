# AI Service (FastAPI) for Pet Marketplace

## Architecture
Next.js frontend -> Laravel backend -> FastAPI ai-service -> OpenAI model

`product_search` remains rule-based today.  
`description_generator` now supports multi-provider low-token generation:
- OpenAI (`gpt-4o-mini`)
- Gemini Flash
- Ollama local model
- Template fallback

## 1) Create virtual environment
```bash
cd ai-service
python -m venv venv
```

## 2) Activate venv (Windows)
```bash
venv\Scripts\activate
```

## 3) Install requirements
```bash
pip install -r requirements.txt
```

## 4) Configure environment
Create/update `ai-service/.env`:
```env
AI_SERVICE_HOST=127.0.0.1
AI_SERVICE_PORT=8001
OPENAI_API_KEY=your_api_key_here
OPENAI_MODEL=gpt-4o-mini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=phi3
AI_PROVIDER=auto
```

## 5) Run FastAPI
```bash
uvicorn main:app --reload --host 127.0.0.1 --port 8001
```

## 6) Endpoints
- `GET /`
- `GET /health`
- `POST /ai/product-search` (rule-based parser)
- `POST /api/ai/generate-description` (OpenAI/Gemini/Ollama/Template auto-fallback)
- `POST /ai/description-generator` (legacy compatibility endpoint)
- `POST /ai/pet-chatbot` (OpenAI)

## Product Search Engine (Best Lightweight Setup)
`/ai/product-search` is optimized for low cost and speed:
- Rule-based NLP
- Typo correction + fuzzy token matching
- Keyword/category mapping
- Price/location/brand entity extraction
- Optional MiniLM semantic category inference (off by default)

Optional semantic mode:
```env
AI_USE_MINILM=true
AI_MINILM_MODEL=sentence-transformers/all-MiniLM-L6-v2
```

If you enable MiniLM, install:
```bash
pip install sentence-transformers
```

## Example: Product Search (rule-based)
```http
POST http://127.0.0.1:8001/ai/product-search
Content-Type: application/json

{
  "query": "I need good food for my kitten under 1000 BDT",
  "user_id": 1
}
```

Example queries you can use in UI:
- `I need good food for my kitten under 1000 BDT`
- `toy for my puppy under 500`
- `medicine for cat in Dhaka`
- `fish aquarium product below 1500`
- `dog collar under 800`
- `pet bed for small dog`
- `bird food in Mirpur`
- `cat grooming product under 1200`

## Example: Description Generator (Low-token, multi-provider)
```http
POST http://127.0.0.1:8001/api/ai/generate-description
Content-Type: application/json

{
  "name": "Premium Chicken Dog Food 5kg",
  "category": "Dog Food",
  "pet_type": "Dog",
  "age_group": "Adult",
  "price": 1850,
  "brand": "Royal Canin",
  "features": ["high protein", "easy digestion", "balanced nutrition"],
  "stock": 35,
  "target_location": "Bangladesh"
}
```

Response shape:
```json
{
  "source": "ai_model",
  "title": "Royal Canin Premium Chicken Dog Food 5kg",
  "description": "Short description under 70 words...",
  "seo_keywords": ["dog food bangladesh", "..."],
  "benefits": ["...", "...", "..."]
}
```

## Example: Pet Chatbot (OpenAI)
```http
POST http://127.0.0.1:8001/ai/pet-chatbot
Content-Type: application/json

{
  "message": "My kitten is not eating well for two days. What should I do?",
  "pet_type": "cat",
  "locale": "Dhaka"
}
```

## Laravel integration
Laravel should call FastAPI using backend env:
```env
AI_SERVICE_URL=http://127.0.0.1:8001
```

Frontend should continue calling Laravel API only.

## Error handling behavior
- If `OPENAI_API_KEY` is missing, OpenAI endpoints return `503`.
- If OpenAI request fails (network/provider), endpoints return a clean `500` with message.
- `product_search` endpoint behavior is unchanged and does not require OpenAI.

## Troubleshooting
### FastAPI not running
- Verify `uvicorn` is running on `127.0.0.1:8001`.
- Test `GET http://127.0.0.1:8001/health`.

### OpenAI/Gemini key error
- Add a valid `OPENAI_API_KEY` and/or `GEMINI_API_KEY` in `ai-service/.env`.
- Restart FastAPI after env changes.

### Using Ollama
- Ensure Ollama is running locally (`ollama serve`).
- Pull model, for example:
```bash
ollama pull phi3
```
- Keep `AI_PROVIDER=auto` or set `AI_PROVIDER=ollama`.

### Laravel connection refused
- Confirm `AI_SERVICE_URL` in Laravel backend `.env`.
- Run:
```bash
php artisan config:clear
```

### CORS issue
- Allowed origins are configured for Laravel local:
  - `http://localhost:8000`
  - `http://127.0.0.1:8000`

### No AI response text
- Check API key validity and quota.
- Check internet connectivity from server.
