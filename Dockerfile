# ---- Build frontend assets ----
FROM node:20-alpine AS nodebuild
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Install PHP dependencies (NO scripts here) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# IMPORTANT: --no-scripts prevents "php artisan package:discover" during build stage
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ---- Final runtime ----
FROM php:8.2-apache

# Cloud Run listens on 8080
ENV PORT=8080
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Apache config for Laravel public/
RUN a2enmod rewrite headers \
 && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '\n<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

# System deps + PHP extensions (Postgres, zip etc.)
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy app source
COPY . .

# Copy vendor + built assets
COPY --from=vendor /app/vendor ./vendor
COPY --from=nodebuild /app/public/build ./public/build

# Ensure Laravel writable dirs exist (just in case)
RUN mkdir -p storage/framework/{cache/data,sessions,views} bootstrap/cache

# Permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Avoid running artisan in Docker build (can fail without APP_KEY in CI)
# Instead, just remove any cached files if present:
RUN rm -f bootstrap/cache/*.php || true
