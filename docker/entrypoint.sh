#!/bin/sh
set -eu

cd /app

data="${PARTYOS_DATA:-/var/lib/partyos}"

mkdir -p \
    "${data}/storage/app/public" \
    "${data}/storage/app/private" \
    "${data}/storage/framework/cache/data" \
    "${data}/storage/framework/sessions" \
    "${data}/storage/framework/views" \
    "${data}/storage/logs" \
    /app/bootstrap/cache

if [ -L /app/storage ]; then
    rm -f /app/storage
elif [ -d /app/storage ]; then
    rm -rf /app/storage
fi

ln -s "${data}/storage" /app/storage

if [ ! -f "${data}/database.sqlite" ]; then
    touch "${data}/database.sqlite"
fi

if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s "${data}/app.key" ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > "${data}/app.key"
        chmod 600 "${data}/app.key"
    fi

    APP_KEY=$(cat "${data}/app.key")
    export APP_KEY
fi

echo "PartyOS: migrando banco e preparando o admin"
php artisan config:clear --no-interaction
php artisan migrate --force --no-interaction
php artisan partyos:bootstrap --no-interaction
php artisan storage:link --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:clear --no-interaction
php artisan view:cache --no-interaction
php artisan event:clear --no-interaction
php artisan event:cache --no-interaction

echo "PartyOS: pronto em ${APP_URL:-http://127.0.0.1:1437}"

if [ -x /usr/local/bin/docker-php-entrypoint ]; then
    exec /usr/local/bin/docker-php-entrypoint "$@"
fi

exec "$@"
