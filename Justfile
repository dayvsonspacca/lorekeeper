list:
    @just --list

install:
    composer install

test *args:
    php -dmemory_limit=-1 ./vendor/bin/phpunit -c config/phpunit.xml.dist {{ args }}

lint *args:
    ./vendor/bin/mago --config config/mago.toml lint {{ args }}

fmt *args:
    ./vendor/bin/mago --config config/mago.toml fmt {{ args }}

analyze *args:
    ./vendor/bin/mago --config config/mago.toml analyze {{ args }}

# Enforce layer boundaries (core ← protocol ← miner → persistence → core)
guard *args:
    ./vendor/bin/mago --config config/mago.toml guard {{ args }}

# Run a mining routine (e.g. `just mine travel-map`, `just mine listen --user=Foo`)
mine *args:
    php packages/miner/scripts/mine.php {{ args }}
