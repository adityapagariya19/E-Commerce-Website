#!/usr/bin/env bash
# Local-only browser database manager (Adminer — a single PHP file, like a lightweight phpMyAdmin).
# Usage:  ./tools/db-browser.sh        then open http://127.0.0.1:8081
# It binds to 127.0.0.1 ONLY, so it is not reachable from other machines. Never run this on a public server.
set -euo pipefail
cd "$(dirname "$0")"
VERSION="${ADMINER_VERSION:-6.3.0}"
FILE="adminer-${VERSION}-mysql.php"
if [ ! -f "$FILE" ]; then
  echo "Downloading Adminer ${VERSION} from GitHub…"
  curl -fL -o "$FILE" "https://github.com/vrana/adminer/releases/download/v${VERSION}/${FILE}" \
   || { echo "Download failed. Get Adminer from https://www.adminer.org/ and save it as tools/${FILE}"; exit 1; }
fi
mkdir -p .adminer && cp "$FILE" .adminer/index.php
echo "Adminer running at http://127.0.0.1:8081  (System: MySQL, Server: 127.0.0.1, Database: jb_optician)"
echo "Log in with YOUR OWN MySQL user/password in the browser — nothing is stored. Ctrl+C to stop."
exec php -S 127.0.0.1:8081 -t .adminer
