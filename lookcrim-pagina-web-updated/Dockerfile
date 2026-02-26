# syntax=docker/dockerfile:1.6

########################
# 1) Build frontend assets
########################
FROM node:20-bookworm-slim AS frontend-build
WORKDIR /app

COPY frontend/package.json frontend/package-lock.json ./frontend/
RUN cd frontend && npm ci

COPY frontend ./frontend
COPY server/public ./server/public

# Vite está configurado para compilar hacia ../server/public/build
RUN cd frontend && npm run build

########################
# 2) Install PHP deps (vendor)
########################
FROM composer:2 AS vendor
WORKDIR /app/server

COPY server/composer.json server/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY server ./
RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chmod -R ug+rwX bootstrap/cache storage || true
RUN composer dump-autoload -o --no-interaction
RUN php artisan package:discover --ansi || true

########################
# 3) Runtime (nginx + php-fpm)
########################
FROM php:8.2-fpm-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

# System deps
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        nginx \
        supervisor \
        netcat-openbsd \
        gettext-base \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        intl \
        zip \
        gd \
        opcache

# App
WORKDIR /var/www/html
COPY --chown=www-data:www-data server ./
COPY --from=vendor --chown=www-data:www-data /app/server/vendor ./vendor
COPY --from=frontend-build --chown=www-data:www-data /app/server/public/build ./public/build

# Nginx
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf || true \
    && mkdir -p /etc/nginx/templates
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template

# PHP config (uploads)
COPY docker/php-custom.ini /usr/local/etc/php/conf.d/zzz-uploads.ini

# Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
