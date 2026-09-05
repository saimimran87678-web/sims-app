#!/usr/bin/env bash

# ==============================================================================
# SIMS Server Control Manager
# Production Service Orchestrator (Nginx, PHP-FPM, Redis, PM2)
# ==============================================================================

# Resolve actual script path even if invoked via symlink (e.g., /usr/local/bin/sims)
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
    DIR="$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )"
    SOURCE="$(readlink "$SOURCE")"
    [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
done
SCRIPT_DIR="$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )"

if [ -f "$SCRIPT_DIR/artisan" ]; then
    APP_DIR="$SCRIPT_DIR"
elif [ -f "/home/saim/SIMS/sims-app/artisan" ]; then
    APP_DIR="/home/saim/SIMS/sims-app"
else
    echo "❌ Error: Could not locate SIMS project root directory (artisan not found)."
    exit 1
fi

cd "$APP_DIR" || exit 1

# Detect installed PHP version dynamically (defaults to 8.3)
if command -v php >/dev/null 2>&1; then
    PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)"
fi
PHP_VER="${PHP_VER:-8.3}"
PHP_FPM="php${PHP_VER}-fpm"

# Pre-flight verification helper
check_environment() {
    local missing=0
    for binary in nginx pm2 redis-server; do
        if ! command -v "$binary" >/dev/null 2>&1; then
            echo "⚠️ [Warning] Required tool '$binary' is not installed on this system."
            missing=$((missing + 1))
        fi
    done

    if command -v php >/dev/null 2>&1; then
        if ! php -m 2>/dev/null | grep -qi "redis"; then
            echo "⚠️ [Warning] PHP Redis extension (php${PHP_VER}-redis) is not loaded."
        fi
    fi

    if [ "$missing" -gt 0 ]; then
        echo "💡 Refer to INSTALLATION_GUIDE.md for installation instructions."
        echo ""
    fi
}

ACTION="${1:-status}"

case "$ACTION" in
    start)
        echo "🚀 Starting SIMS Production Services..."
        check_environment

        # Test Nginx syntax if available
        if command -v nginx >/dev/null 2>&1; then
            sudo nginx -t -q 2>/dev/null || echo "⚠️ Warning: Nginx configuration test returned warnings/errors."
        fi

        sudo systemctl start redis-server "$PHP_FPM" nginx
        pm2 start "$APP_DIR/ecosystem.config.cjs" 2>/dev/null || pm2 restart all
        echo "✅ All SIMS services (Nginx, $PHP_FPM, Redis, PM2) are ONLINE!"
        echo "🌐 App URL: http://localhost"
        ;;
        
    stop)
        echo "🛑 Stopping SIMS Production Services..."
        pm2 stop all 2>/dev/null
        sudo systemctl stop nginx "$PHP_FPM" redis-server
        echo "💤 All SIMS services (Nginx, $PHP_FPM, Redis, PM2) have been STOPPED!"
        ;;
        
    restart)
        echo "🔄 Restarting SIMS Production Services..."
        check_environment

        if command -v nginx >/dev/null 2>&1; then
            sudo nginx -t -q 2>/dev/null || echo "⚠️ Warning: Nginx configuration test returned warnings/errors."
        fi

        sudo systemctl restart redis-server "$PHP_FPM" nginx
        pm2 restart all 2>/dev/null
        php artisan app:optimize
        echo "⚡ All SIMS services RESTARTED and OPTIMIZED!"
        ;;
        
    status)
        echo "=========================================================="
        echo "           📊 SIMS PRODUCTION SYSTEM DASHBOARD            "
        echo "=========================================================="
        echo -n "🌐 Nginx Web Server:    "
        systemctl is-active --quiet nginx 2>/dev/null && echo "🟢 ACTIVE (Listening on http://localhost)" || echo "🔴 INACTIVE"
        
        echo -n "🐘 PHP-FPM ($PHP_FPM): "
        systemctl is-active --quiet "$PHP_FPM" 2>/dev/null && echo "🟢 ACTIVE (FastCGI Service)" || echo "🔴 INACTIVE"
        
        echo -n "🚀 Redis Cache & Queue: "
        systemctl is-active --quiet redis-server 2>/dev/null && echo "🟢 ACTIVE (RAM Storage)" || echo "🔴 INACTIVE"
        
        echo -n "🔌 PHP Redis Extension: "
        if command -v php >/dev/null 2>&1 && php -m 2>/dev/null | grep -qi "redis"; then
            echo "🟢 LOADED (php${PHP_VER}-redis)"
        else
            echo "🔴 NOT LOADED (php${PHP_VER}-redis missing)"
        fi
        echo "=========================================================="
        echo "🔄 PM2 Background Process Workers:"
        if command -v pm2 >/dev/null 2>&1; then
            pm2 status
        else
            echo "🔴 PM2 is not installed. (Run: sudo npm install -g pm2)"
        fi
        ;;

    install-cli)
        echo "🔗 Linking 'sims' command to /usr/local/bin/sims..."
        sudo chmod +x "$SCRIPT_DIR/sims-server.sh"
        sudo ln -sf "$SCRIPT_DIR/sims-server.sh" /usr/local/bin/sims
        echo "✅ Done! You can now manage SIMS globally from any terminal directory using:"
        echo "   sims start"
        echo "   sims stop"
        echo "   sims restart"
        echo "   sims status"
        ;;
        
    *)
        echo "Usage: $0 {start|stop|restart|status|install-cli}"
        exit 1
        ;;
esac
