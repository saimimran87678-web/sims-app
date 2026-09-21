#!/usr/bin/env bash
set -e

echo "===================================================="
echo "     🐧 SIMS School Management System Installer     "
echo "===================================================="
echo ""

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
APP_DIR="${ROOT_DIR}/sims-app"

# 1. Verify PHP installation
if ! command -v php >/dev/null 2>&1; then
    echo "❌ Error: PHP is not installed on this system."
    echo "Please install PHP 8.2+ and SQLite extension first:"
    echo "   sudo apt update && sudo apt install -y php8.2 php8.2-sqlite3 php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip"
    exit 1
fi

echo "Step 1/2: Initializing database, cryptographic keys, and caches..."
echo "------------------------------------------------------------------"
cd "${APP_DIR}"
php artisan sims:install

echo ""
echo "Step 2/2: Registering systemd background services (auto-boot)..."
echo "------------------------------------------------------------------"
sudo php artisan sims:setup-linux

echo ""
echo "===================================================="
echo " 🎉 SIMS Installation Complete!"
echo "===================================================="
echo "Services are online and configured to restart automatically on reboot."
echo ""
