#!/bin/sh
set -e

until [ -f vendor/autoload.php ] && [ -f .env ] && php artisan migrate:status --no-interaction > /dev/null 2>&1; do
    sleep 2
done

exec php artisan bots:supervise
