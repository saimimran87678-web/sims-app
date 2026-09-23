#!/usr/bin/env bash
set -e

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." >/dev/null 2>&1 && pwd )"
APP_DIR="${ROOT_DIR}/sims-app"

echo "Starting SIMS Web Server and Background Workers..."
cd "${APP_DIR}"

# Check FrankenPHP first, fallback to PHP built-in server
if [ -f "${ROOT_DIR}/runtime/frankenphp" ] && [ -x "${ROOT_DIR}/runtime/frankenphp" ]; then
    nohup "${ROOT_DIR}/runtime/frankenphp" php-server --listen :80 --root "${APP_DIR}/public" >/dev/null 2>&1 &
elif command -v frankenphp >/dev/null 2>&1; then
    nohup frankenphp php-server --listen :80 --root "${APP_DIR}/public" >/dev/null 2>&1 &
else
    PHP_CLI_SERVER_WORKERS=4 nohup php -S 0.0.0.0:80 -t "${APP_DIR}/public" >/dev/null 2>&1 &
fi

# Start Queue worker
nohup php "${APP_DIR}/artisan" queue:work --sleep=3 --tries=3 >/dev/null 2>&1 &

echo "[OK] Web server and queue worker launched in background."
SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")
echo "Access URL: http://${SERVER_IP} or http://localhost"
