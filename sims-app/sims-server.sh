#!/usr/bin/env bash

# SIMS Server Control Manager (Location-Agnostic Script)

# Determine SIMS project root directory automatically
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
if [ -f "$SCRIPT_DIR/artisan" ]; then
    APP_DIR="$SCRIPT_DIR"
else
    APP_DIR="/home/saim/SIMS/sims-app"
fi

cd "$APP_DIR" || exit 1

ACTION="${1:-status}"

case "$ACTION" in
    start)
        echo "🚀 Starting SIMS Production Services..."
        sudo systemctl start redis-server php8.3-fpm nginx
        pm2 start "$APP_DIR/ecosystem.config.cjs" 2>/dev/null || pm2 restart all
        echo "✅ All SIMS services (Nginx, PHP-FPM, Redis, PM2) are ONLINE!"
        echo "🌐 App URL: http://localhost"
        ;;
        
    stop)
        echo "🛑 Stopping SIMS Production Services..."
        pm2 stop all 2>/dev/null
        sudo systemctl stop nginx php8.3-fpm redis-server
        echo "💤 All SIMS services (Nginx, PHP-FPM, Redis, PM2) have been STOPPED!"
        ;;
        
    restart)
        echo "🔄 Restarting SIMS Production Services..."
        sudo systemctl restart redis-server php8.3-fpm nginx
        pm2 restart all 2>/dev/null
        php artisan app:optimize
        echo "⚡ All SIMS services RESTARTED and OPTIMIZED!"
        ;;
        
    status)
        echo "=========================================================="
        echo "           📊 SIMS PRODUCTION SYSTEM DASHBOARD            "
        echo "=========================================================="
        echo -n "🌐 Nginx Web Server:    "
        systemctl is-active --quiet nginx && echo "🟢 ACTIVE (Listening on http://localhost)" || echo "🔴 INACTIVE"
        
        echo -n "🐘 PHP-FPM Engine:      "
        systemctl is-active --quiet php8.3-fpm && echo "🟢 ACTIVE (PHP 8.3 FastCGI)" || echo "🔴 INACTIVE"
        
        echo -n "🚀 Redis Cache & Queue: "
        systemctl is-active --quiet redis-server && echo "🟢 ACTIVE (RAM Storage)" || echo "🔴 INACTIVE"
        echo "=========================================================="
        echo "🔄 PM2 Background Process Workers:"
        pm2 status
        ;;
        
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac
