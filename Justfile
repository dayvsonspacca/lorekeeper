mod desktop 'apps/desktop/Justfile'

list:
    @just --list

install target='packages':
    cd {{ if target == 'packages' { '.' } else { 'apps/' + target } }} && composer install

test target='packages' *args:
    {{ if target == 'packages' { 'php -dmemory_limit=-1 ./vendor/bin/phpunit -c config/phpunit.xml.dist' } else { 'cd apps/' + target + ' && vendor/bin/phpunit' } }} {{ args }}

coverage target='packages' *args:
    {{ if target == 'packages' { 'php -dmemory_limit=-1 ./vendor/bin/phpunit -c config/phpunit.xml.dist --coverage-html var/coverage' } else { 'cd apps/' + target + ' && vendor/bin/phpunit --coverage-html ../../var/coverage/' + target } }} --coverage-text --only-summary-for-coverage-text {{ args }}

lint *args:
    ./vendor/bin/mago --config config/mago.toml lint {{ args }}

fmt *args:
    ./vendor/bin/mago --config config/mago.toml fmt {{ args }}

analyze *args:
    ./vendor/bin/mago --config config/mago.toml analyze {{ args }}

# Enforce layer boundaries (core ← server ← client ← bot)
guard *args:
    ./vendor/bin/mago --config config/mago.toml guard {{ args }}
