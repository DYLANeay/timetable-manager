# Stage 1: Build frontend
FROM node:22-alpine AS frontend
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --legacy-peer-deps
COPY frontend/ .
ENV VITE_API_URL=/api
RUN npm run build

# Stage 2: PHP backend
FROM php:8.3-cli
RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql pgsql zip bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY backend/ .
RUN composer dump-autoload --optimize

# Copy built frontend assets into Laravel public (index.html handled by catch-all route)
COPY --from=frontend /app/dist/ /app/public/

EXPOSE 8000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000
