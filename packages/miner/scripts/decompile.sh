#!/usr/bin/env bash
set -euo pipefail

FFDEC_VERSION="26.2.1"

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
TOOLS_DIR="$(dirname -- "$SCRIPT_DIR")/tools/ffdec-$FFDEC_VERSION"
FFDEC="$TOOLS_DIR/ffdec.sh"

[ $# -eq 2 ] || {
    echo "Usage: $(basename "$0") <file.swf> <output-dir>" >&2
    exit 2
}

if [ ! -f "$FFDEC" ]; then
    tmp="$(mktemp -d)"
    trap 'rm -rf "$tmp"' EXIT
    curl -fsSL "https://github.com/jindrapetrik/jpexs-decompiler/releases/download/version${FFDEC_VERSION}/ffdec_${FFDEC_VERSION}.zip" -o "$tmp/ffdec.zip"
    mkdir -p "$TOOLS_DIR"
    unzip -q "$tmp/ffdec.zip" -d "$TOOLS_DIR"
    chmod +x "$FFDEC"
fi

"$FFDEC" -cli -export script,text "$2" "$1" >/dev/null
"$FFDEC" -cli -swf2xml "$1" "$2/map.xml" >/dev/null
