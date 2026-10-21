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

# Enforce layer boundaries (core ← server ← client ← bot)
guard *args:
    ./vendor/bin/mago --config config/mago.toml guard {{ args }}

# Make the GitHub labels match .github/labels.json, deleting any label not listed there
labels:
    #!/usr/bin/env sh
    set -e
    jq -c '.[]' .github/labels.json | while read -r label; do
        gh label create "$(echo "$label" | jq -r .name)" --color "$(echo "$label" | jq -r .color)" --description "$(echo "$label" | jq -r .description)" --force
    done
    gh label list --limit 200 --json name -q '.[].name' | while read -r name; do
        jq -e --arg name "$name" 'any(.[]; .name == $name)' .github/labels.json > /dev/null || gh label delete "$name" --yes
    done
