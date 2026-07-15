# --- Stage 1: Build Frontend Assets ---
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# --- Stage 2: Build PHP Application ---
FROM php:8.4-cli-alpine
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    bash \
    && docker-php-ext-install pdo pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .
COPY --from=node-builder /app/public/build ./public/build

# Copy default production env if .env is missing
RUN cp .env.example .env

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Laravel directories
RUN chmod -R 777 storage bootstrap/cache

# Environment configurations
ENV PORT=8080
EXPOSE 8080

# Configure run script
RUN chmod +x docker/run.sh

CMD ["/bin/bash", "docker/run.sh"]
