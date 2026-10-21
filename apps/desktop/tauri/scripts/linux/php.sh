#!/usr/bin/env bash
set -euo pipefail

TAURI=$(cd "$(dirname "$0")/../.." && pwd)
WORK=$TAURI/.php
EXTENSIONS=bcmath,ctype,curl,dom,fileinfo,filter,iconv,intl,mbstring,opcache,openssl,pcntl,pdo,pdo_sqlite,phar,posix,session,simplexml,sockets,sqlite3,tokenizer,xml,xmlreader,xmlwriter,zlib

mkdir -p "$WORK"
cd "$WORK"
if [ ! -x spc ]; then
    curl -fsSL -o spc https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-linux-x86_64
    chmod +x spc
fi

export SPC_LIBC=glibc
./spc doctor --auto-fix
./spc download --for-extensions="$EXTENSIONS" --with-php=8.5 --prefer-pre-built
./spc build "$EXTENSIONS" --build-cli
install -m 0755 buildroot/bin/php "$WORK/php"

"$WORK/php" -v
