import os
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv

from routers.product_search import router as product_search_router
from routers.description_generator import router as description_router
from routers.pet_chatbot import router as chatbot_router

load_dotenv()

app = FastAPI(title="Pet Marketplace AI Service", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:8000",
        "http://127.0.0.1:8000",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/")
def root():
    return {"status": "ok", "service": "ai-service", "version": "1.0.0"}


@app.get("/health")
def health():
    return {"success": True, "service": "ai-service", "health": "ok"}


app.include_router(product_search_router)
app.include_router(description_router)
app.include_router(chatbot_router)
