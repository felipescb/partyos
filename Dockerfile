# syntax=docker/dockerfile:1

FROM --platform=linux/amd64 dunglas/frankenphp:1-php8.4 AS php-base

RUN install-php-extensions \
        bcmath \
        intl \
        pcntl \
        zip

COPY docker/php.ini /usr/local/etc/php/conf.d/partyos.ini

FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE= \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory:

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --no-autoloader

COPY . .

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi --no-interaction

FROM --platform=linux/amd64 node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN mkdir -p vendor/livewire/flux-pro/stubs \
    && npm run build

FROM php-base AS runtime

WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_LOCALE=pt_BR \
    APP_FALLBACK_LOCALE=pt_BR \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/lib/partyos/database.sqlite \
    DB_FOREIGN_KEYS=true \
    DB_BUSY_TIMEOUT=5000 \
    DB_JOURNAL_MODE=WAL \
    DB_SYNCHRONOUS=NORMAL \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database \
    FILESYSTEM_DISK=local \
    MAIL_MAILER=log \
    PARTYOS_ALLOW_REGISTRATION=false \
    PARTYOS_DATA=/var/lib/partyos \
    TRUSTED_PROXIES=* \
    SERVER_NAME=:80 \
    ADMIN_NAME=Admin \
    ADMIN_EMAIL=admin@partyos.local \
    ADMIN_PASSWORD=partyos

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && mkdir -p /var/lib/partyos /app/storage /app/bootstrap/cache

VOLUME ["/var/lib/partyos"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1/up") === false ? 1 : 0);'

ENTRYPOINT ["/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
