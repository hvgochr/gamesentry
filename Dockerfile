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
# Frontend production build
# ---------------------------------------------------------------------------

FROM php-base AS frontend-build

# Node 22 and this image are both Debian Bookworm based, so copying /usr/local
# gives us node, npm and their global runtime files without installing Node
# again through an external package repository.
COPY --from=node:22-bookworm-slim /usr/local/ /usr/local/

COPY --from=composer-production /app/vendor /app/vendor

COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

COPY . .

RUN npm ci

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative

RUN npm run build


# ---------------------------------------------------------------------------
# Production
# ---------------------------------------------------------------------------

FROM php-base AS production

COPY docker/php/php.prod.ini /usr/local/etc/php/conf.d/99-app.ini

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

EXPOSE 80

CMD ["frankenphp", "php-server", "-r", "public/"]
