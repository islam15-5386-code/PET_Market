# 🐾 Pet Marketplace — Full-Stack Application

A complete Dockerized pet marketplace built with **Laravel 11** (API) + **Next.js 14** (frontend) + **PostgreSQL** + **pgAdmin**.

---

## 🗂️ Project Structure

```
petmarket/
├── docker-compose.yml          ← Dev environment (use this to run everything)
├── docker-compose.prod.yml     ← Production environment
├── Dockerfile                  ← PHP-FPM image (auto-initializes on first run)
├── Makefile                    ← Handy shortcuts
├── docker/
│   ├── entrypoint.sh           ← Auto: composer install, migrate, seed, jwt:secret
│   ├── nginx/
│   │   ├── default.conf        ← Dev Nginx config
│   │   └── prod.conf           ← Prod Nginx config
│   └── php/
│       ├── php.ini
│       └── www.conf
├── src/                        ← Laravel backend (mounted into container)
│   ├── .env                    ← Pre-configured for Docker (edit mail settings)
│   ├── app/
│   ├── database/
│   ├── routes/api.php          ← All API routes (phases 1-5)
│   └── ...
└── frontend/                   ← Next.js frontend (mounted into container)
    ├── .env.local              ← Points to http://localhost:8000/api
    ├── src/
    │   ├── app/                ← App Router pages
    │   ├── components/         ← UI, layout, product, cart, admin components
    │   ├── context/            ← AuthContext
    │   ├── hooks/              ← Custom hooks
    │   ├── lib/                ← API client and helpers
    │   └── types/              ← TypeScript types
    └── ...
```

---

## 🚀 Quick Start — Run Everything with One Command

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- Ports **3000**, **8000**, **5432**, **5050** must be free

### Steps

```bash
# 1. Unzip and enter the project directory
cd petmarket

# 2. Start everything
docker-compose up --build
```

That is it. On first boot the backend container will automatically:
1. Install Composer dependencies
2. Generate `APP_KEY`
3. Generate `JWT_SECRET`
4. Run all database migrations
5. Seed the database (admin user + categories + sample products)

**Wait about 60-90 seconds** for all services to initialize, then open:

| Service        | URL                           | Credentials                    |
|----------------|-------------------------------|--------------------------------|
| Frontend       | http://localhost:3000         |                                |
| Backend API    | http://localhost:8000/api     |                                |
| Health Check   | http://localhost:8000/api/health |                             |
| pgAdmin        | http://localhost:5050         | admin@petmarket.com / admin123 |

---

## Default Credentials

| Role  | Email                   | Password  |
|-------|-------------------------|-----------|
| Admin | admin@petmarket.com     | password  |

Register new customer accounts via the frontend or the `/api/auth/register` endpoint.

---

## API Overview

### Visit the site to view the published documentation: 
https://documenter.getpostman.com/view/54061227/2sBXqJKg2X

Base URL: `http://localhost:8000/api`

### Auth
| Method | Endpoint               | Auth | Description       |
|--------|------------------------|------|-------------------|
| POST   | /auth/register         | No   | Register          |
| POST   | /auth/login            | No   | Login / JWT token |
| POST   | /auth/logout           | JWT  | Logout            |
| POST   | /auth/refresh          | JWT  | Refresh token     |
| GET    | /auth/me               | JWT  | Current user      |
| POST   | /auth/forgot-password  | No   | Request reset     |
| POST   | /auth/reset-password   | No   | Confirm reset     |

### Products and Categories (public)
| Method | Endpoint          | Description           |
|--------|-------------------|-----------------------|
| GET    | /categories       | List all categories   |
| GET    | /products         | List / search / filter|
| GET    | /products/{slug}  | Product detail        |

### User (JWT required)
| Method | Endpoint              | Description       |
|--------|-----------------------|-------------------|
| GET    | /user/profile         | Get profile       |
| PUT    | /user/profile         | Update profile    |
| POST   | /user/profile/avatar  | Upload avatar     |
| GET    | /user/orders          | Order history     |

### Cart (JWT required)
| Method | Endpoint           | Description     |
|--------|--------------------|-----------------|
| GET    | /cart              | View cart       |
| POST   | /cart              | Add to cart     |
| PUT    | /cart/{productId}  | Update quantity |
| DELETE | /cart/{productId}  | Remove item     |
| DELETE | /cart              | Clear cart      |

### Orders (JWT required)
| Method | Endpoint                     | Description     |
|--------|------------------------------|-----------------|
| POST   | /orders/preview              | Preview total   |
| POST   | /orders                      | Place order     |
| GET    | /orders/{orderNumber}        | Order detail    |
| POST   | /orders/{orderNumber}/cancel | Cancel order    |

### Admin (JWT + admin role required)
| Method | Endpoint                              | Description          |
|--------|---------------------------------------|----------------------|
| GET    | /admin/dashboard                      | Dashboard stats      |
| GET    | /admin/dashboard/revenue              | Revenue chart data   |
| GET/POST | /admin/products                    | List / create        |
| GET/PUT/DELETE | /admin/products/{id}         | Read / update / delete|
| POST   | /admin/products/{id}/images           | Upload images        |
| GET    | /admin/users                          | List users           |
| POST   | /admin/users/{id}/block               | Block user           |
| POST   | /admin/users/{id}/unblock             | Unblock user         |
| GET    | /admin/orders                         | List orders          |
| PUT    | /admin/orders/{orderNumber}/status    | Update status        |

---

## Common Commands

```bash
# Start in background
docker-compose up -d --build

# View logs
docker-compose logs -f app        # Laravel
docker-compose logs -f frontend   # Next.js

# Stop everything
docker-compose down

# Full reset (removes DB data)
docker-compose down -v

# Run artisan commands
docker-compose exec app php artisan <command>

# Laravel shell
docker-compose exec app bash

# Node shell
docker-compose exec frontend sh
```

---

## Environment Configuration

### Backend (`src/.env`)
Pre-configured for local Docker. Key settings to customize:
- `MAIL_*` — Add Mailtrap credentials for password reset emails
- `AWS_*` / `FILESYSTEM_DISK=s3` — For cloud image storage (optional)

### Frontend (`frontend/.env.local`)
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_APP_NAME="Pet Marketplace"
```

---

## Tech Stack

| Layer     | Technology                                         |
|-----------|----------------------------------------------------|
| Backend   | Laravel 11, PHP 8.3, php-open-source-saver/jwt-auth|
| Frontend  | Next.js 14, React 18, TypeScript, Tailwind CSS     |
| Database  | PostgreSQL 17                                      |
| Web Server| Nginx Alpine                                       |
| DB Admin  | pgAdmin 4                                          |
| Container | Docker + Docker Compose                            |

---

## Troubleshooting

**App keeps restarting after boot**
```bash
docker-compose logs app
# Usually the DB wasn't ready — just restart the app service:
docker-compose restart app
```

**Port conflict**
Edit `docker-compose.yml` and change the host port (left side of `:`):
```yaml
nginx:
  ports:
    - "8080:80"   # change 8000 to any free port
```

**Complete fresh start**
```bash
docker-compose down -v --rmi local
docker-compose up --build
```
