# ─────────────────────────────────────────────────────────────────────────────
# Pet Marketplace — Makefile
# Usage: make <command>
# ─────────────────────────────────────────────────────────────────────────────

.PHONY: help dev prod down logs shell-app shell-fe migrate seed test deploy

# Default: show help
help:
	@echo ""
	@echo "  🐾 Pet Marketplace — Available Commands"
	@echo ""
	@echo "  Development:"
	@echo "    make dev          Start dev environment"
	@echo "    make down         Stop all containers"
	@echo "    make logs         Tail app + nginx logs"
	@echo "    make shell-app    Open bash in Laravel container"
	@echo "    make shell-fe     Open sh in Next.js container"
	@echo ""
	@echo "  Database:"
	@echo "    make migrate      Run migrations"
	@echo "    make seed         Run all seeders"
	@echo "    make fresh        Drop + re-migrate + seed"
	@echo ""
	@echo "  Testing:"
	@echo "    make test         Run PHPUnit tests"
	@echo "    make lint         Run Laravel Pint"
	@echo ""
	@echo "  Production:"
	@echo "    make prod         Start production environment"
	@echo "    make prod-build   Rebuild production images"
	@echo "    make cache        Cache config, routes, views"
	@echo "    make deploy       Pull + migrate + cache (on server)"
	@echo ""

# ── Development ───────────────────────────────────────────────────────────────

dev:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f app nginx

shell-app:
	docker exec -it petmarket_app bash

shell-fe:
	docker exec -it petmarket_frontend sh

# ── Database ──────────────────────────────────────────────────────────────────

migrate:
	docker exec petmarket_app php artisan migrate

seed:
	docker exec petmarket_app php artisan db:seed

fresh:
	docker exec petmarket_app php artisan migrate:fresh --seed

# ── Testing ───────────────────────────────────────────────────────────────────

test:
	docker exec petmarket_app php artisan test --parallel

lint:
	docker exec petmarket_app ./vendor/bin/pint

# ── Production ────────────────────────────────────────────────────────────────

prod:
	docker compose -f docker-compose.prod.yml up -d

prod-build:
	docker compose -f docker-compose.prod.yml up -d --build

prod-down:
	docker compose -f docker-compose.prod.yml down

prod-logs:
	docker compose -f docker-compose.prod.yml logs -f app nginx

cache:
	docker exec petmarket_app_prod php artisan config:cache
	docker exec petmarket_app_prod php artisan route:cache
	docker exec petmarket_app_prod php artisan view:cache

deploy:
	git pull origin main
	docker exec petmarket_app_prod composer install --no-dev --optimize-autoloader
	docker exec petmarket_app_prod php artisan migrate --force
	$(MAKE) cache
	docker restart petmarket_app_prod
	@echo "✅ Deployed"
