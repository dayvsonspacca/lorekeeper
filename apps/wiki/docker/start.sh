#!/bin/sh
set -e

composer install --no-interaction --quiet

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --no-interaction
fi

php artisan migrate --force --no-interaction

exec frankenphp php-server --root public --listen :80
