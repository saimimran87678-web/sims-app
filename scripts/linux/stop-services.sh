#!/usr/bin/env bash
set -e

echo "Stopping SIMS Linux services..."

# Stop systemd services if registered
if command -v systemctl >/dev/null 2>&1; then
    sudo systemctl stop sims-web sims-queue sims-scheduler 2>/dev/null || true
fi

# Kill any remaining background processes
pkill -f "frankenphp" 2>/dev/null || true
pkill -f "artisan queue:work" 2>/dev/null || true
pkill -f "artisan schedule:work" 2>/dev/null || true
pkill -f "php -S 0.0.0.0:80" 2>/dev/null || true

echo "[OK] All SIMS Linux services have been stopped."
