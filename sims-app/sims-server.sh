#!/usr/bin/env bash

# SIMS Server Control Manager (Unified Start/Stop/Status)

ACTION="${1:-status}"

case "$ACTION" in
    start)
        echo "🚀 Starting SIMS Production Services..."
        sudo systemctl start redis-server nginx
        pm2 start ecosystem.config.cjs 2>/dev/null || pm2 restart all
        echo "✅ All SIMS services (Nginx, Redis, PM2) are ONLINE!"
        echo "🌐 App URL: http://localhost"
        ;;
        
    stop)
        echo "🛑 Stopping SIMS Production Services..."
        pm2 stop all 2>/dev/null
        sudo systemctl stop nginx redis-server
        echo "💤 All SIMS services (Nginx, Redis, PM2) have been STOPPED!"
        ;;
        
    restart)
        echo "🔄 Restarting SIMS Production Services..."
        sudo systemctl restart redis-server nginx
        pm2 restart all 2>/dev/null
        php artisan app:optimize
        echo "⚡ All SIMS services RESTARTED and OPTIMIZED!"
        ;;
        
    status)
        echo "📊 --- SIMS System Status ---"
        echo -n "Nginx Status: "
        systemctl is-active nginx 2>/dev/null || echo "inactive"
        echo -n "Redis Status: "
        systemctl is-active redis-server 2>/dev/null || echo "inactive"
        echo ""
        echo "PM2 Background Workers Status:"
        pm2 status
        ;;
        
    *)
        echo "Usage: ./sims-server.sh {start|stop|restart|status}"
        exit 1
        ;;
esac
