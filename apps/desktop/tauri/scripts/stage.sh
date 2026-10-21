#!/usr/bin/env bash
set -euo pipefail

TAURI=$(cd "$(dirname "$0")/.." && pwd)
DESKTOP=$(cd "$TAURI/.." && pwd)
ROOT=$(cd "$DESKTOP/../.." && pwd)
PHP_BINARY=${1:?usage: scripts/stage.sh /path/to/static/php}
BUILD=$TAURI/build
WORK=$BUILD/.work

sources() {
    git -C "$1" ls-files -z --cached --others --exclude-standard . | grep -zv -E "$2" || true
}

rm -rf "$BUILD"
mkdir -p "$WORK/apps/desktop" "$WORK/packages" "$BUILD/php"

sources "$DESKTOP" '^(tests|tauri)/|^phpunit\.xml$|^\.env' \
    | rsync -a --from0 --files-from=- --ignore-missing-args "$DESKTOP/" "$WORK/apps/desktop/"
for package in core server client bot; do
    mkdir -p "$WORK/packages/$package"
    sources "$ROOT/packages/$package" '^tests/' \
        | rsync -a --from0 --files-from=- --ignore-missing-args "$ROOT/packages/$package/" "$WORK/packages/$package/"
done

cd "$WORK/apps/desktop"
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev --optimize-autoloader --no-interaction --quiet

find vendor -type d \( -name tests -o -name .github \) -prune -exec rm -rf {} +
rm -rf storage bootstrap/cache
mkdir -p storage bootstrap/cache
cat > .env <<'ENV'
APP_NAME=Lorekeeper
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=single
ENV

mv "$WORK/apps/desktop" "$BUILD/app"
rm -rf "$WORK"
install -m 0755 "$PHP_BINARY" "$BUILD/php/php"

du -sh "$BUILD/app" "$BUILD/php"
