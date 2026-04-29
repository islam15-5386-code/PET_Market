#!/bin/bash
set -e

echo "🐾 Pet Marketplace — Starting Laravel..."

# ── Load .env for shell use ───────────────────────────────────────────────────
if [ -f /var/www/.env ]; then
  export $(grep -v '^#' /var/www/.env | grep -v '^$' | xargs)
fi

# ── Wait for Postgres ─────────────────────────────────────────────────────────
echo "⏳ Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST:-postgres};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-pet_marketplace}', '${DB_USERNAME:-petmarket}', '${DB_PASSWORD:-petmarket123}');" 2>/dev/null; do
  sleep 1
done
echo "✅ PostgreSQL is ready"

# ── Ensure we are in the Laravel root ────────────────────────────────────────
cd /var/www

if [ ! -f "/var/www/artisan" ]; then
  echo "❌ ERROR: artisan not found at /var/www/artisan"
  echo "   Make sure ./src is mounted to /var/www in docker-compose.yml"
  exit 1
fi

# ── Install Composer dependencies if vendor is missing ───────────────────────
if [ ! -d "/var/www/vendor" ]; then
  echo "📦 Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# ── Ensure laravel/socialite is installed (FR-01: Social Login) ──────────────
if [ ! -d "/var/www/vendor/laravel/socialite" ]; then
  echo "📦 Installing laravel/socialite..."
  composer require laravel/socialite:^5.16 --no-interaction --prefer-dist --update-with-all-dependencies
fi

# ── Generate APP_KEY if missing ───────────────────────────────────────────────
if [ -z "$(grep '^APP_KEY=.\+' /var/www/.env)" ]; then
  echo "🔑 Generating APP_KEY..."
  php artisan key:generate --force
fi

# ── Generate JWT secret if missing ───────────────────────────────────────────
if [ -z "$(grep '^JWT_SECRET=.\+' /var/www/.env)" ]; then
  echo "🔐 Generating JWT_SECRET..."
  php artisan jwt:secret --force
fi

# ── Run migrations ────────────────────────────────────────────────────────────
echo "🗄️  Running database migrations..."
php artisan migrate --force

# ── Run seeders ───────────────────────────────────────────────────────────────
echo "🌱 Seeding database..."
php artisan db:seed --force

# ── Storage link ──────────────────────────────────────────────────────────────
echo "🔗 Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

# ── Fix permissions ───────────────────────────────────────────────────────────
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "🚀 Pet Marketplace API is ready!"

# ── Start PHP-FPM ─────────────────────────────────────────────────────────────
exec php-fpm
