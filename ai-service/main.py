from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from routers.embeddings import router as embeddings_router
from routers.description_generator import router as description_router
from routers.pet_chatbot import router as chatbot_router
from routers.product_search import router as product_search_router
from routers.search import router as search_router
from routers.smart_router import router as smart_router

app = FastAPI(title="Pet Marketplace AI Service", version="2.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:3000",
        "http://127.0.0.1:3000",
        "http://localhost:8000",
        "http://127.0.0.1:8000",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/")
def root():
    return {"status": "ok", "service": "ai-service", "version": "2.0.0"}


@app.get("/health")
def health():
    return {
        "status": "ok",
        "service": "ai-service",
        "version": "1.0.0",
        "success": True,
        "health": "ok",
    }


app.include_router(search_router)
app.include_router(smart_router)
app.include_router(product_search_router)
app.include_router(embeddings_router)
app.include_router(description_router)
app.include_router(chatbot_router)
