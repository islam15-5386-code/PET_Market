import os

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse

from routers.description_generator import router as description_router
from routers.health import router as health_router
from routers.pet_chatbot import router as chatbot_router
from routers.product_search import router as product_search_router

APP_VERSION = "1.0.0"
APP_SERVICE = os.getenv("APP_NAME", "pet-ai-service")

frontend_url = os.getenv("APP_FRONTEND_URL", "http://localhost:3000").rstrip("/")
backend_url = os.getenv("BACKEND_API_URL", "http://localhost:8000").rstrip("/")
extra_origins = [origin.strip() for origin in os.getenv("CORS_ORIGINS", "").split(",") if origin.strip()]
allowed_origins = [
    "http://localhost:8000",
    "http://127.0.0.1:8000",
    "http://localhost:3000",
    "http://127.0.0.1:3000",
    frontend_url,
    backend_url,
    *extra_origins,
]

app = FastAPI(title="Pet Marketplace AI Service", version=APP_VERSION)

app.add_middleware(
    CORSMiddleware,
    allow_origins=list(dict.fromkeys(allowed_origins)),
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(_: Request, exc: RequestValidationError):
    return JSONResponse(status_code=422, content={"error": "validation_error", "detail": exc.errors()})


@app.exception_handler(Exception)
async def unhandled_exception_handler(_: Request, __: Exception):
    return JSONResponse(status_code=500, content={"error": "internal_server_error", "message": "Something went wrong."})


app.include_router(health_router)
app.include_router(product_search_router)
app.include_router(description_router)
app.include_router(chatbot_router)


@app.get("/health")
def root_health():
    return {"status": "ok", "service": APP_SERVICE, "version": APP_VERSION}
