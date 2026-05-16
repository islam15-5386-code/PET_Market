# Free-Tier Deployment Guide

This project deploys best on a split architecture:

- Frontend (Next.js): **Vercel**
- Backend API (Laravel): **Render Web Service (free)**
- AI Service (FastAPI): **Render Web Service (free)**
- PostgreSQL: **Neon free Postgres** (recommended) or Supabase free Postgres

---

## 1) Final Architecture

Browser  
-> Vercel frontend  
-> Laravel API on Render  
-> PostgreSQL (Neon/Supabase)  
-> FastAPI AI service on Render  
-> Laravel API  
-> Frontend

Secrets stay only on backend/AI service. Frontend only gets `NEXT_PUBLIC_*` vars.

---

## 2) Service Mapping

- `frontend/` -> Vercel project
- `src/` -> Render PHP service (`pet-marketplace-api`)
- `ai-service/` -> Render Python service (`pet-ai-service`)
- DB -> Neon project/database

Blueprint file included: [render.yaml](../render.yaml)

---

## 3) Frontend (Vercel) Setup

Create a Vercel project with root: `frontend`.

### Required Vercel env vars

```env
NEXT_PUBLIC_API_URL=https://YOUR_BACKEND_API_URL/api
NEXT_PUBLIC_APP_URL=https://YOUR_VERCEL_FRONTEND_URL
NEXT_PUBLIC_AI_ENABLED=true
```

### Build settings

- Install command: `npm ci`
- Build command: `npm run build`

`frontend/vercel.json` is already added.

---

## 4) Laravel Backend Setup (Render)

Create a Render **Web Service** with root: `src` (or use `render.yaml`).

### Required backend env vars

```env
APP_NAME="Pet Marketplace"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_BACKEND_API_URL
APP_FRONTEND_URL=https://YOUR_VERCEL_FRONTEND_URL

DB_CONNECTION=pgsql
DB_HOST=YOUR_DB_HOST
DB_PORT=5432
DB_DATABASE=YOUR_DB_NAME
DB_USERNAME=YOUR_DB_USER
DB_PASSWORD=YOUR_DB_PASSWORD

AI_SERVICE_URL=https://YOUR_AI_SERVICE_URL
AI_TIMEOUT_SECONDS=30

JWT_SECRET=YOUR_JWT_SECRET
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
CACHE_STORE=file
LOG_CHANNEL=stack

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://YOUR_BACKEND_API_URL/api/auth/google/callback
FRONTEND_URL=https://YOUR_VERCEL_FRONTEND_URL
```

### Notes

- CORS uses `APP_FRONTEND_URL` dynamically (`src/config/cors.php`).
- API health: `GET /api/health`.
- If using local uploads, ensure `php artisan storage:link` is run in build/start.

---

## 5) FastAPI AI Service Setup (Render)

Create a Render **Web Service** with root: `ai-service`.

### Required AI service env vars

```env
APP_NAME=pet-ai-service
APP_ENV=production
PORT=10000

BACKEND_API_URL=https://YOUR_BACKEND_API_URL
APP_FRONTEND_URL=https://YOUR_VERCEL_FRONTEND_URL
CORS_ORIGINS=https://YOUR_VERCEL_FRONTEND_URL,https://YOUR_BACKEND_API_URL

DATABASE_URL=postgresql://USER:PASSWORD@HOST:5432/DB_NAME

AI_PROVIDER=auto
AI_TIMEOUT_SECONDS=30
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
MODEL_NAME=gpt-4o-mini
```

### Start command

```bash
uvicorn main:app --host 0.0.0.0 --port $PORT
```

Health endpoints:

- `GET /health`
- `GET /ai/health`

---

## 6) PostgreSQL (Neon) Setup

1. Create Neon project + database.
2. Copy connection details.
3. Put DB env vars in Laravel and optionally AI service `DATABASE_URL`.
4. Deploy backend, then run migrations and seed.

---

## 7) Migrations and Seeding

For free tier, use lightweight seed by default (already in `DatabaseSeeder`).

```bash
php artisan migrate --force
php artisan db:seed --force
```

Do **not** seed 1,000,000 products on free tier unless explicitly needed.

---

## 8) Auth + CORS Notes

- Current auth uses JWT bearer token.
- Frontend sends `Authorization: Bearer <token>`.
- Backend CORS allows `APP_FRONTEND_URL`.
- Google OAuth callback route:
  - `/api/auth/google/callback`
- Frontend callback page:
  - `/auth/google/callback`

---

## 9) Image Handling in Production

- Frontend local fallback images: `/products/fallback/pet-product-placeholder.jpg`
- Backend should return image paths consistently.
- `frontend/next.config.js` now supports:
  - local/dev backend storage
  - configured API host from `NEXT_PUBLIC_API_URL`
  - common external image hosts

---

## 10) AI Feature Deployment Verification

After deploy:

1. AI Search:
   - `kitten food under 1000 bdt`
   - `dog toy under 500`
   - `বিড়ালের খাবার ১০০০ টাকার মধ্যে`
2. AI Description generator (admin-only)
3. Chatbot
4. Emergency prompt:
   - `My cat is not eating`
   - Expect vet warning and no unsafe dosage advice.

---

## 11) Final Test Checklist

Frontend:
- homepage loads
- product list/detail works
- login/register/logout works
- cart/checkout/orders work
- admin dashboard works
- images load with fallback

Backend:
- `/api/health` returns ok
- auth APIs work
- product/order/cart APIs work
- CORS works from Vercel domain

AI:
- `/health` returns ok
- `/ai/product-search` works
- description generate endpoint works
- chatbot works

Security:
- no secrets in frontend
- JWT protected routes enforce auth/role
- production origins restricted

---

## 12) Common Errors + Fixes

### CORS blocked
- Ensure `APP_FRONTEND_URL` is exact Vercel URL.
- Clear Laravel config cache after env changes:
  - `php artisan config:clear`

### 401 after login
- Confirm frontend `NEXT_PUBLIC_API_URL` points to backend `/api`.
- Check JWT secret is set in backend env.

### AI timeout
- Increase `AI_TIMEOUT_SECONDS`.
- Validate AI service URL and health endpoint.

### Broken images
- Verify product paths/fallback values.
- Ensure backend storage/public URLs are correct.

