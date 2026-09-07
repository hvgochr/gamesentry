# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# PHP base
# ---------------------------------------------------------------------------

FROM dunglas/frankenphp:1-php8.4-bookworm AS php-base

WORKDIR /app

RUN install-php-extensions \
    bcmath \
    intl \
    opcache \
    pcntl \
    pdo_pgsql \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ---------------------------------------------------------------------------
# Development
# ---------------------------------------------------------------------------

FROM php-base AS development

ENV APP_ENV=local

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-scripts

COPY . .

RUN composer dump-autoload


# ---------------------------------------------------------------------------
# Node development
# ---------------------------------------------------------------------------

FROM node:22-bookworm-slim AS node-development

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .


# ---------------------------------------------------------------------------
# Frontend build
# ---------------------------------------------------------------------------

FROM node:22-bookworm-slim AS frontend-build

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build


# ---------------------------------------------------------------------------
# Composer production dependencies
# ---------------------------------------------------------------------------

FROM php-base AS composer-production

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# ---------------------------------------------------------------------------
# Production
# ---------------------------------------------------------------------------

FROM php-base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV SERVER_NAME=:8080

COPY --from=composer-production /app/vendor /app/vendor

COPY . .

COPY --from=frontend-build /app/public/build /app/public/build

RUN composer dump-autoload \
        --no-dev \
        --classmap-authoritative \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD ["frankenphp", "php-server", "-r", "public/"]