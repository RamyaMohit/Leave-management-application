# --- Stage 1: build frontend assets with Node/Vite ---
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN npm run build

# --- Stage 2: PHP application ---
FROM php:8.2-cli

# System deps + PHP extensions Laravel 10 + Guzzle need
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev sqlite3 \
    libxml2-dev libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql zip bcmath mbstring xml dom curl \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (better Docker layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

# Copy the rest of the app
COPY . .

# Bring in the built frontend assets from stage 1
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize

# SQLite database file + writable storage
RUN mkdir -p database && touch database/database.sqlite \
    && chmod -R 775 storage bootstrap/cache database

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
