#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PARENT="$(dirname "$ROOT")"
OUT="$PARENT/onkupon-autonomous-commerce-agent.zip"
TMP="$PARENT/.onkupon-agent-build"
rm -rf "$TMP" "$OUT"
mkdir -p "$TMP/onkupon-autonomous-commerce-agent"
rsync -a --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude 'dist' \
  --exclude 'tests' \
  --exclude '*.zip' \
  "$ROOT/" "$TMP/onkupon-autonomous-commerce-agent/"
( cd "$TMP" && zip -qr "$OUT" onkupon-autonomous-commerce-agent )
rm -rf "$TMP"
echo "Built $OUT"
