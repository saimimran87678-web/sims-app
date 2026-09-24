#!/usr/bin/env bash
set -e

ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." >/dev/null 2>&1 && pwd )"
APP_DIR="${ROOT_DIR}/sims-app"

ACTION="${1:-}"

case "${ACTION}" in
    start)
        echo "Starting SIMS Linux services..."
        if command -v systemctl >/dev/null 2>&1; then
            sudo systemctl start sims-web sims-queue sims-scheduler 2>/dev/null || sudo systemctl start sims-web
        else
            "${ROOT_DIR}/scripts/linux/start-server.sh"
        fi
        echo "[OK] Services started."
        ;;
    stop)
        echo "Stopping SIMS Linux services..."
        if command -v systemctl >/dev/null 2>&1; then
            sudo systemctl stop sims-web sims-queue sims-scheduler 2>/dev/null || true
        else
            "${ROOT_DIR}/scripts/linux/stop-services.sh"
        fi
        echo "[OK] Services stopped."
        ;;
    restart)
        echo "Restarting SIMS Linux services..."
        if command -v systemctl >/dev/null 2>&1; then
            sudo systemctl restart sims-web sims-queue sims-scheduler 2>/dev/null || sudo systemctl restart sims-web
        else
            "${ROOT_DIR}/scripts/linux/stop-services.sh"
            "${ROOT_DIR}/scripts/linux/start-server.sh"
        fi
        echo "[OK] Services restarted."
        ;;
    status)
        echo "===================================================="
        echo "             SIMS Linux Service Status"
        echo "===================================================="
        if command -v systemctl >/dev/null 2>&1; then
            systemctl status sims-web --no-pager 2>/dev/null || echo "sims-web: Not installed / inactive"
        else
            pgrep -fl "php" || echo "No active PHP processes found."
        fi
        SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")
        echo "===================================================="
        echo "Access URLs:"
        echo "  - http://localhost"
        echo "  - http://${SERVER_IP}"
        echo "===================================================="
        ;;
    activate)
        cd "${APP_DIR}"
        php artisan license:activate "${2:-}" "${3:-}"
        ;;
    update)
        echo "===================================================="
        echo "          SIMS Safe System Update Manager"
        echo "===================================================="
        cd "${APP_DIR}"
        shift || true
        php artisan sims:update "$@"
        echo "===================================================="
        ;;
    logs)
        if command -v journalctl >/dev/null 2>&1; then
            journalctl -u sims-web -n 50 --no-pager
        else
            tail -n 50 "${APP_DIR}/storage/logs/laravel.log" 2>/dev/null || echo "No log file found."
        fi
        ;;
    *)
        echo "===================================================="
        echo "             SIMS Linux Service Manager"
        echo "===================================================="
        echo "Usage: ./sims.sh [command] [options]"
        echo ""
        echo "Commands:"
        echo "  status        - Display status of SIMS web and worker services"
        echo "  start         - Start all services (systemd / background)"
        echo "  stop          - Stop all services"
        echo "  restart       - Restart all services"
        echo "  activate [key]- Activate school license"
        echo "  update        - Check and apply automated delta updates safely"
        echo "  logs          - View recent service and application logs"
        echo "===================================================="
        exit 0
        ;;
esac
