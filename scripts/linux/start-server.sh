#!/usr/bin/env bash
set -e

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." >/dev/null 2>&1 && pwd )"
APP_DIR="${ROOT_DIR}/sims-app"

echo "Starting SIMS Web Server and Background Workers..."
cd "${APP_DIR}"

# Check FrankenPHP first, fallback to PHP built-in server
PORT="${PORT:-8000}"
if [ -f "${ROOT_DIR}/runtime/frankenphp" ] && [ -x "${ROOT_DIR}/runtime/frankenphp" ]; then
    setsid "${ROOT_DIR}/runtime/frankenphp" run --config "${APP_DIR}/Caddyfile.frankenphp" > "${APP_DIR}/storage/logs/frankenphp.log" 2>&1 < /dev/null &
elif command -v frankenphp >/dev/null 2>&1; then
    setsid frankenphp run --config "${APP_DIR}/Caddyfile.frankenphp" > "${APP_DIR}/storage/logs/frankenphp.log" 2>&1 < /dev/null &
else
    PHP_CLI_SERVER_WORKERS=4 setsid php -S 0.0.0.0:${PORT} -t "${APP_DIR}/public" >/dev/null 2>&1 < /dev/null &
fi

# Start Queue worker if not already running
if ! pgrep -f "artisan queue:work" >/dev/null 2>&1; then
    nohup php "${APP_DIR}/artisan" queue:work --sleep=3 --tries=3 >/dev/null 2>&1 &
fi

echo "[OK] FrankenPHP web server (4 workers) and queue worker launched."
SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")
echo "Access URL: http://${SERVER_IP}:${PORT} or http://localhost:${PORT}"
