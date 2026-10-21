#!/usr/bin/env bash
set -euo pipefail

TAURI=$(cd "$(dirname "$0")/../.." && pwd)
WORK=$TAURI/.php

SPC_VERSION=2.8.5
SPC_SHA256=523ba4279c54c7a377156c0dd3a36adf92ee64b01e9a7f5e9e2ec084b8e458e5
EXTENSIONS=bcmath,ctype,curl,dom,fileinfo,filter,iconv,intl,mbstring,opcache,openssl,pcntl,pdo,pdo_sqlite,phar,posix,session,simplexml,sockets,sqlite3,tokenizer,xml,xmlreader,xmlwriter,zlib
SOURCES=(
    "php-src https://www.php.net/distributions/php-8.5.12.tar.xz 08bc2eba2a588654ade1357174aa646b761556c862dda0678345ee29ed481209"
    "micro https://github.com/static-php/phpmicro/archive/fb6d497b6f4cf138ee3851a30c905d64b7b19aed.tar.gz 810548718d9e54e8003f569b393399bdb4adf3a6bc59ef9af55b6f6931b34215"
    "zlib https://zlib.net/fossils/zlib-1.3.2.tar.gz bb329a0a2cd0274d05519d61c667c062e06990d72e125ee2dfa8de64f0119d16"
    "openssl https://github.com/openssl/openssl/releases/download/openssl-3.6.5/openssl-3.6.5.tar.gz a2157c2830efdec3788939b00c9b0638306d3f0bbb76dc4832ee503bb397df98"
    "libiconv https://ftp.gnu.org/pub/gnu/libiconv/libiconv-1.19.tar.gz 88dd96a8c0464eca144fc791ae60cd31cd8ee78321e67397e25fc095c4a19aa6"
    "libxml2 https://download.gnome.org/sources/libxml2/2.15/libxml2-2.15.4.tar.xz 98087fd181d9070724f3fbc65c7377db03038eb92bd882374daff44940138821"
    "curl https://curl.se/download/curl-8.23.0.tar.xz 8dadd7d2536a314f982ad05a651b713f426bf7d02cc2c76cffb9796f2218e6c5"
    "icu https://github.com/unicode-org/icu/releases/download/release-77-1/icu4c-77_1-src.tgz 588e431f77327c39031ffbb8843c0e3bc122c211374485fa87dc5f3faff24061"
    "sqlite https://www.sqlite.org/2024/sqlite-autoconf-3450200.tar.gz bc9067442eedf3dd39989b5c5cfbfff37ae66cc9c99274e0c3052dc4d4a8f6ae"
)

downloaded() {
    php -r '$lock = json_decode((string) @file_get_contents("downloads/.lock.json"), true); echo $lock[$argv[1]]["filename"] ?? "";' "$1"
}

pinned() {
    local name url sha256 file
    for source in "${SOURCES[@]}"; do
        read -r name url sha256 <<< "$source"
        file=$(downloaded "$name")
        if [ -z "$file" ] || ! echo "$sha256  downloads/$file" | sha256sum --check --quiet --status; then
            echo "$name"
        fi
    done
}

mkdir -p "$WORK"
cd "$WORK"

if [ ! -x spc ] || ! ./spc --version | grep -qx "static-php-cli $SPC_VERSION"; then
    curl -fsSL -o spc.tar.gz "https://github.com/crazywhalecc/static-php-cli/releases/download/$SPC_VERSION/spc-linux-x86_64.tar.gz"
    echo "$SPC_SHA256  spc.tar.gz" | sha256sum --check --quiet
    tar xzf spc.tar.gz spc
    rm spc.tar.gz
fi

names=()
urls=()
for source in "${SOURCES[@]}"; do
    read -r name url _ <<< "$source"
    names+=("$name")
    urls+=(--custom-url "$name:$url")
done

export SPC_LIBC=glibc
./spc doctor --auto-fix

stale=$(pinned | paste -sd, -)
if [ -n "$stale" ]; then
    ./spc download "$(IFS=,; echo "${names[*]}")" "${urls[@]}" --ignore-cache-sources="$stale"
fi
stale=$(pinned | paste -sd, -)
if [ -n "$stale" ]; then
    echo "These sources do not match their pinned SHA-256: $stale" >&2
    exit 1
fi

./spc build "$EXTENSIONS" --build-cli
install -m 0755 buildroot/bin/php "$WORK/php"

"$WORK/php" -v
