# Deployment Guide (Free Tier)

This monorepo is deployed as:

- `frontend/` (Next.js) -> **Vercel**
- `backend/` (Laravel API) -> **Render Web Service**
- `ai-service/` (FastAPI) -> **Render Web Service**
- PostgreSQL -> **Neon** or **Supabase**
- Files/images -> **Cloudinary**

## 1) Deployment order

1. Create PostgreSQL (Neon/Supabase)
2. Deploy FastAPI (`ai-service`) on Render
3. Deploy Laravel (`backend`) on Render
4. Deploy Next.js (`frontend`) on Vercel
5. Update cross-service URLs env vars and redeploy

---

## 2) Database setup (Neon/Supabase)

Create a Postgres DB and copy:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

or full URL for Python:

- `DATABASE_URL=postgresql://...`

---

## 3) Render: FastAPI service (`ai-service`)

- **Root Directory:** `ai-service`
- **Build Command:** `pip install -r requirements.txt`
- **Start Command:** `uvicorn main:app --host 0.0.0.0 --port $PORT`
- **Health Check Path:** `/health`

### Required env vars (AI service)

- `APP_ENV=production`
- `APP_NAME=pet-ai-service`
- `BACKEND_API_URL=https://<your-backend>.onrender.com`
- `APP_FRONTEND_URL=https://<your-frontend>.vercel.app`
- `CORS_ORIGINS=https://<your-frontend>.vercel.app,https://<your-backend>.onrender.com`
- `DATABASE_URL=postgresql://...` (Neon/Supabase)
- `AI_PROVIDER=gemini` (or `auto`)
- `GEMINI_API_KEY=...` (or `OPENAI_API_KEY`)
- `GEMINI_MODEL=gemini-1.5-flash`

---

## 4) Render: Laravel API service (`backend`)

- **Root Directory:** `backend`
- **Build Command:**
  - `composer install --no-dev --optimize-autoloader`
  - `php artisan config:clear`
  - `php artisan route:clear`
  - `php artisan view:clear`
  - `php artisan storage:link || true`
- **Start Command:**
  - `php artisan migrate --force`
  - `php -S 0.0.0.0:$PORT -t public public/index.php`
- **Health Check Path:** `/api/health`

### Required env vars (Laravel)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://<your-backend>.onrender.com`
- `FRONTEND_URL=https://<your-frontend>.vercel.app`
- `CORS_ALLOWED_ORIGINS=https://<your-frontend>.vercel.app`
- `APP_KEY=<generate in Laravel>`
- `JWT_SECRET=<generate once>`
- `DB_CONNECTION=pgsql`
- `DB_HOST=...`
- `DB_PORT=5432`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`
- `AI_SERVICE_URL=https://<your-ai-service>.onrender.com`
- `FILESYSTEM_DISK=cloudinary`
- `CLOUDINARY_CLOUD_NAME=...`
- `CLOUDINARY_API_KEY=...`
- `CLOUDINARY_API_SECRET=...`

### Laravel one-time commands (manual)

Run once in Render shell (or pre-deploy job):

- `php artisan key:generate --show` -> set as `APP_KEY`
- `php artisan jwt:secret --force` -> set as `JWT_SECRET`
- `php artisan migrate --force`
- `php artisan db:seed --force` (optional; only if needed)
- `php artisan config:cache` (after all env vars are final)

---

## 5) Vercel: Next.js frontend (`frontend`)

- Import repo to Vercel
- **Root Directory:** `frontend`
- Build/Start scripts already present in `package.json`
  - build: `next build`
  - start: `next start`

### Required env vars (Frontend)

- `NEXT_PUBLIC_API_URL=https://<your-backend>.onrender.com/api`
- `NEXT_PUBLIC_APP_URL=https://<your-frontend>.vercel.app`
- `NEXT_PUBLIC_AI_ENABLED=true`
- `NEXT_PUBLIC_AI_SERVICE_URL=https://<your-ai-service>.onrender.com` (only if frontend calls AI directly)

---

## 6) Cloudinary note

Production uploads should use Cloudinary credentials and `FILESYSTEM_DISK=cloudinary`.
If Cloudinary Laravel driver is not installed in your runtime image, install:

- `composer require cloudinary-labs/cloudinary-laravel`

---

## 7) Post-deploy checklist

1. `GET https://<backend>/api/health` -> status `ok`
2. `GET https://<ai-service>/health` -> status `ok`
3. Frontend loads products from backend
4. Login/register works
5. Chatbot API works
6. AI product search works
7. Image uploads store via Cloudinary
8. CORS preflight works from Vercel domain

