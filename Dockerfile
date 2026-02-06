# ---- Build frontend assets (Vite) ----
FROM node:20-alpine AS nodebuild
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- PHP vendor stage (PIN PHP VERSION) ----
FROM php:8.2-cli AS vendor
WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

# IMPORTANT: avoid running "php artisan package:discover" during build
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ---- Final runtime ----
FROM php:8.2-apache

ENV PORT=8080
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite headers \
 && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '\n<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

RUN apt-get update && apt-get install -y \
    unzip libpq-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy app source
COPY . /var/www/html

# Copy vendor + built assets
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=nodebuild /app/public/build /var/www/html/public/build

# Writable dirs
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
