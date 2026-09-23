#!/usr/bin/env bash
set -e

echo "===================================================="
echo "     🐧 SIMS School Management System Installer     "
echo "===================================================="
echo ""

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." >/dev/null 2>&1 && pwd )"
APP_DIR="${ROOT_DIR}/sims-app"

# 1. Verify PHP installation
if ! command -v php >/dev/null 2>&1; then
    echo "❌ Error: PHP is not installed on this system."
    echo "Please install PHP 8.2+ and SQLite extension first:"
    echo "   sudo apt update && sudo apt install -y php8.2-cli php8.2-sqlite3 php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip"
    exit 1
fi

# 2. Ensure .env exists before booting Laravel
if [ ! -f "${APP_DIR}/.env" ]; then
    if [ -f "${APP_DIR}/.env.example" ]; then
        echo "ℹ️  .env not found. Creating from .env.example..."
        cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
        echo "✅ Created .env file from .env.example."
    else
        echo "❌ Error: Neither .env nor .env.example was found in ${APP_DIR}!"
        exit 1
    fi
fi

# 3. Ensure permissions
mkdir -p "${APP_DIR}/storage/logs" "${APP_DIR}/storage/framework/views" "${APP_DIR}/storage/framework/sessions" "${APP_DIR}/storage/framework/cache/data" "${APP_DIR}/bootstrap/cache"
chmod -R 777 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" "${APP_DIR}/database" 2>/dev/null || true

echo "Step 1/2: Initializing database, cryptographic keys, and caches..."
echo "------------------------------------------------------------------"
cd "${APP_DIR}"
php artisan sims:install

echo ""
echo "Step 2/2: Registering systemd background services (auto-boot)..."
echo "------------------------------------------------------------------"
if command -v systemctl >/dev/null 2>&1; then
    if [ "$(id -u)" -eq 0 ]; then
        php artisan sims:setup-linux
    elif command -v sudo >/dev/null 2>&1; then
        sudo php artisan sims:setup-linux
    else
        echo "⚠️ Notice: Root or sudo privileges required to install systemd services."
        echo "Run: sudo php artisan sims:setup-linux"
    fi
else
    echo "ℹ️ Notice: systemd not detected (container or minimal OS). Starting web server manually:"
    echo "   cd ${APP_DIR} && php -S 0.0.0.0:80 -t public"
fi

echo ""
echo "===================================================="
echo " 🎉 SIMS Installation Complete!"
echo "===================================================="
SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")
echo "Access the portal in your browser:"
echo "   - http://localhost"
echo "   - http://${SERVER_IP}"
echo ""
