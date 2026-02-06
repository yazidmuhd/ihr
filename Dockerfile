# ---- Build frontend assets ----
FROM node:20-alpine AS nodebuild
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- PHP vendor stage (PIN PHP VERSION) ----
FROM php:8.2-cli AS vendor
WORKDIR /app

# deps for extensions + composer
RUN apt-get update && apt-get install -y \
    git unzip \
    libpq-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
  && rm -rf /var/lib/apt/lists/*

# PHP extensions needed by common Laravel apps + your packages
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install \
    gd pdo_pgsql zip mbstring xml

# install Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# install PHP deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# copy full app source after vendor is built
COPY . .

# ---- Final runtime ----
FROM php:8.2-apache

# (Railway gives you a public domain -> traffic comes in here)
ENV PORT=8080
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite headers \
 && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '\n<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

# runtime deps + extensions
RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd pdo_pgsql zip mbstring xml \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# copy app (already contains /vendor from vendor stage)
COPY --from=vendor /app /var/www/html

# copy built frontend assets
COPY --from=nodebuild /app/public/build /var/www/html/public/build

# writable dirs
RUN mkdir -p storage/framework/{cache/data,sessions,views} bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
