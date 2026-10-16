list:
    @just --list

install:
    composer install

test *args:
    php -dmemory_limit=-1 ./vendor/bin/phpunit -c config/phpunit.xml.dist {{ args }}

# Run the tests with code coverage (needs pcov or Xdebug); the HTML report goes to var/coverage
coverage *args:
    php -dmemory_limit=-1 ./vendor/bin/phpunit -c config/phpunit.xml.dist --coverage-html var/coverage --coverage-text --only-summary-for-coverage-text {{ args }}

lint *args:
    ./vendor/bin/mago --config config/mago.toml lint {{ args }}

fmt *args:
    ./vendor/bin/mago --config config/mago.toml fmt {{ args }}

analyze *args:
    ./vendor/bin/mago --config config/mago.toml analyze {{ args }}

# Enforce layer boundaries (core ← server ← client)
guard *args:
    ./vendor/bin/mago --config config/mago.toml guard {{ args }}
