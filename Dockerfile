# ─────────────────────────────────────────────────────────────────────────────
# Pet Marketplace — PHP 8.3 FPM Image
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        exif \
        pcntl \
        bcmath

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ── Working directory ─────────────────────────────────────────────────────────
WORKDIR /var/www

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
