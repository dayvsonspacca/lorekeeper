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

# Enforce layer boundaries (core ← protocol ← miner → persistence ← wiki)
guard *args:
    ./vendor/bin/mago --config config/mago.toml guard {{ args }}

# Run a mining routine (e.g. `just mine travel-map`, `just mine listen --user=Foo`)
mine *args:
    php packages/miner/scripts/mine.php {{ args }}

# Serve the built wiki (e.g. `just wiki` then open http://127.0.0.1:8765)
wiki port="8765":
    php -S 127.0.0.1:{{ port }} -t packages/wiki/public packages/wiki/public/index.php

# Install the wiki frontend dependencies and build it
wiki-build:
    cd packages/wiki/web && npm install && npm run build

# Develop the wiki: PHP API on 8765 and Vite with hot reload on 5173
wiki-dev:
    php -S 127.0.0.1:8765 -t packages/wiki/public packages/wiki/public/index.php & cd packages/wiki/web && npm run dev

# Compile the wiki Flash previews (needs the Apache Flex SDK); the avatar embeds the skeleton of the current game client
wiki-swf mxmlc="/opt/apache-flex-sdk/bin/mxmlc":
    mkdir -p var/cache/client
    test -f var/cache/client/Game.swf || curl -sfL -A Mozilla/5.0 -o var/cache/client/Game.swf "https://game.aq.com/game/gamefiles/$(curl -sf -A Mozilla/5.0 https://game.aq.com/game/api/data/gameversion | php -r 'echo json_decode(stream_get_contents(STDIN))->sFile;')"
    {{ mxmlc }} -output packages/wiki/web/public/swfs/item.swf -static-link-runtime-shared-libraries=true -default-size 600 450 packages/wiki/flash/ItemPreview.as
    {{ mxmlc }} -output packages/wiki/web/public/swfs/monster.swf -static-link-runtime-shared-libraries=true -default-size 600 450 packages/wiki/flash/MonsterPreview.as
    {{ mxmlc }} -output packages/wiki/web/public/swfs/avatar.swf -static-link-runtime-shared-libraries=true -default-size 600 450 packages/wiki/flash/AvatarPreview.as
