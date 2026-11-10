# syntax=docker/dockerfile:1

# Install Node.js and npm
FROM node:24-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    mysql-client \
    tzdata \
    curl \
    git \
    unzip \
    supervisor \
    autoconf \
    g++ \
    make \
    oniguruma-dev \
    netcat-openbsd

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql bcmath mbstring

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis


# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Copy project
COPY . .

COPY --from=assets /app/public/build /app/public/build

# Install Composer dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
RUN php artisan package:discover --ansi

# Create required directories
RUN mkdir -p \
    storage/logs \
    storage/app/public \
    storage/app/private/ai \
    storage/app/public/fotos \
    bootstrap/cache \
    /var/lib/nginx/logs \
    /var/lib/nginx/tmp/client_body \
    /var/lib/nginx/tmp/proxy \
    /var/lib/nginx/tmp/fastcgi

# Nginx corre como usuario nginx y necesita escribir el archivo subido.
# PHP-FPM sigue usando www-data sobre el código de la aplicación.
RUN chown -R www-data:www-data /app && \
    chown -R nginx:nginx /var/lib/nginx && \
    chmod -R 755 /var/lib/nginx storage bootstrap/cache

# Copy config files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
