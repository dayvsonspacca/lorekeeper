FROM dunglas/frankenphp:1.12-php8.5

RUN install-php-extensions intl zip pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
