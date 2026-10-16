#!/bin/sh
set -e

composer install --no-interaction --quiet

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --no-interaction
fi

touch database/database.sqlite
php artisan migrate --force --no-interaction

if grep -q '^APP_ENV=local' .env; then
    php artisan db:seed --class='Lorekeeper\Apps\Wiki\Database\Seeders\DevelopmentSeeder' --force --no-interaction
fi

exec frankenphp php-server --root public --listen :8000
