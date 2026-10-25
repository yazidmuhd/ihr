# ---- Build frontend assets (Vite) ----
FROM node:20-alpine AS nodebuild
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- PHP vendor stage (pin PHP + required extensions for composer) ----
FROM php:8.2-cli AS vendor
WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_pgsql zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ---- Runtime (PHP built-in server) ----
FROM php:8.2-cli
WORKDIR /app

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_pgsql zip \
 && rm -rf /var/lib/apt/lists/*

# Production PHP settings: errors never shown to visitors, no version header, uploads sized for 5 MB résumés
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'expose_php=Off\nupload_max_filesize=6M\npost_max_size=8M\n' > "$PHP_INI_DIR/conf.d/zz-ihr.ini"

COPY . /app
COPY --from=vendor /app/vendor /app/vendor
COPY --from=nodebuild /app/public/build /app/public/build

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

ENV PORT=8080
EXPOSE 8080

# Web or queue worker, chosen by CONTAINER_ROLE (see docker/start.sh)
RUN chmod +x docker/start.sh
CMD ["sh", "docker/start.sh"]
