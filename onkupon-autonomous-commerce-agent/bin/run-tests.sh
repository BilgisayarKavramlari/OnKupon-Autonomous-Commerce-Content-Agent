#!/usr/bin/env bash
set -euo pipefail
find "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" -name '*.php' -print0 | xargs -0 -n1 php -l
