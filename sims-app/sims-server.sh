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

# State persistence file to remember if server was running before reboot/power cut
STATE_FILE="$APP_DIR/.sims-server.state"

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

# Function to detect and safely free a port if occupied by a conflicting listening process
free_port() {
    local port="$1"
    local service_label="$2"

    # CRITICAL SAFETY GUARD: Never touch port 443 (outbound HTTPS used by IDEs & terminals)
    if [ "$port" -eq 443 ]; then
        return 0
    fi

    # Specific check: if Apache2 is running on port 80 and blocking Nginx
    if [ "$port" -eq 80 ] && systemctl is-active --quiet apache2 2>/dev/null; then
        echo "⚠️ Detected Apache2 running on port 80. Stopping Apache2 so $service_label (Nginx) can bind..."
        sudo systemctl stop apache2 2>/dev/null || true
        sudo systemctl disable apache2 2>/dev/null || true
    fi

    # Query ONLY local TCP sockets strictly in LISTEN state (never outbound client sockets!)
    local pids=""
    if command -v ss >/dev/null 2>&1; then
        pids=$(sudo ss -tlnp "sport = :$port" 2>/dev/null | grep -oP 'pid=\K[0-9]+' | sort -u)
    fi
    if [ -z "$pids" ] && command -v lsof >/dev/null 2>&1; then
        pids=$(sudo lsof -t -iTCP:"$port" -sTCP:LISTEN 2>/dev/null)
    fi

    if [ -n "$pids" ]; then
        for pid in $pids; do
            # Safety guards: Never kill init (1), self ($$), parent ($PPID)
            if [ -z "$pid" ] || [ "$pid" -le 1 ] || [ "$pid" -eq "$$" ] || [ "$pid" -eq "$PPID" ]; then
                continue
            fi
            local proc_name
            proc_name=$(ps -p "$pid" -o comm= 2>/dev/null || true)

            # Never kill IDE, SSH, or terminal connection processes
            case "$proc_name" in
                *ssh*|*code*|*language_server*|*terminal*|*pts*|*systemd*)
                    continue
                    ;;
            esac

            echo "⚠️ Port $port is held by conflicting listening process '$proc_name' (PID: $pid)."
            echo "   Terminating '$proc_name' to free port $port for $service_label..."
            sudo kill -9 "$pid" 2>/dev/null || true
        done
        sleep 1
    fi
}

ACTION="${1:-status}"

case "$ACTION" in
    start)
        echo "🚀 Starting SIMS Production Services..."
        check_environment

        # Clear conflicting port locks if services are not cleanly active
        if ! systemctl is-active --quiet nginx 2>/dev/null; then
            free_port 80 "Nginx Web Server"
        fi
        if ! systemctl is-active --quiet redis-server 2>/dev/null; then
            free_port 6379 "Redis Server"
        fi
        free_port 3000 "WhatsApp Microservice (Port 3000)"
        free_port 3001 "WhatsApp Microservice (Port 3001)"

        # Test Nginx syntax if available
        if command -v nginx >/dev/null 2>&1; then
            sudo nginx -t -q 2>/dev/null || echo "⚠️ Warning: Nginx configuration test returned warnings/errors."
        fi

        sudo systemctl start redis-server "$PHP_FPM" nginx
        
        # If Nginx failed to start, force clear port 80 and retry
        if ! systemctl is-active --quiet nginx 2>/dev/null; then
            echo "⚠️ Nginx initial start failed. Forcing port 80 release and retrying..."
            free_port 80 "Nginx Web Server"
            sudo systemctl restart nginx 2>/dev/null || true
        fi

        pm2 start "$APP_DIR/ecosystem.config.cjs" 2>/dev/null || pm2 restart all
        pm2 save 2>/dev/null || true

        # Record active state for auto-boot recovery
        echo "RUNNING" > "$STATE_FILE"

        echo "✅ All SIMS services (Nginx, $PHP_FPM, Redis, PM2) are ONLINE!"
        echo "🌐 App URL: http://localhost"
        echo "💾 Persistence State: RUNNING (Auto-recovery on reboot/power restore is ACTIVE)"
        ;;
        
    stop)
        echo "🛑 Stopping SIMS Production Services..."
        
        # Stop background workers and freeze stopped state in PM2
        pm2 stop all 2>/dev/null || true
        pm2 save 2>/dev/null || true
        
        sudo systemctl stop nginx "$PHP_FPM" redis-server

        # Record stopped state so server does NOT auto-start on next boot
        echo "STOPPED" > "$STATE_FILE"

        echo "💤 All SIMS services (Nginx, $PHP_FPM, Redis, PM2) have been STOPPED!"
        echo "💾 Persistence State: STOPPED (Server will NOT auto-start on next reboot)"
        ;;
        
    restart)
        echo "🔄 Restarting SIMS Production Services..."
        check_environment

        # Gracefully stop services and clear all port locks
        pm2 stop all 2>/dev/null || true
        sudo systemctl stop nginx "$PHP_FPM" redis-server 2>/dev/null || true
        free_port 80 "Nginx Web Server"
        free_port 6379 "Redis Server"
        free_port 3000 "WhatsApp Microservice (Port 3000)"
        free_port 3001 "WhatsApp Microservice (Port 3001)"

        if command -v nginx >/dev/null 2>&1; then
            sudo nginx -t -q 2>/dev/null || echo "⚠️ Warning: Nginx configuration test returned warnings/errors."
        fi

        sudo systemctl restart redis-server "$PHP_FPM" nginx
        pm2 restart all 2>/dev/null
        pm2 save 2>/dev/null || true
        
        echo "RUNNING" > "$STATE_FILE"
        php artisan app:optimize
        echo "⚡ All SIMS services RESTARTED, PORT-CLEARED, and OPTIMIZED!"
        echo "💾 Persistence State: RUNNING (Auto-recovery on reboot/power restore is ACTIVE)"
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

        echo -n "💾 Persistence State:   "
        if [ -f "$STATE_FILE" ] && grep -q "RUNNING" "$STATE_FILE"; then
            echo "🟢 RUNNING (Will auto-start on reboot / power recovery)"
        else
            echo "🔴 STOPPED (Will stay inactive on next boot)"
        fi

        echo "----------------------------------------------------------"
        echo "🔌 Port Listeners Check:"
        for p in 80 6379 3000 3001 8000; do
            if command -v lsof >/dev/null 2>&1; then
                p_owner=$(sudo lsof -i :"$p" -sTCP:LISTEN -Fcp 2>/dev/null | tr '\n' ' ' || true)
            fi
            if [ -n "$p_owner" ]; then
                echo "   Port $p: 🔒 BUSY ($p_owner)"
            else
                echo "   Port $p: 🟢 OPEN / AVAILABLE"
            fi
        done
        echo "=========================================================="
        echo "🔄 PM2 Background Process Workers:"
        if command -v pm2 >/dev/null 2>&1; then
            pm2 status
        else
            echo "🔴 PM2 is not installed. (Run: sudo npm install -g pm2)"
        fi
        ;;

    auto-boot)
        # This action is called by systemd on system startup
        echo "🔄 [SIMS Auto-Boot] Checking previous server power state..."
        if [ -f "$STATE_FILE" ] && grep -q "RUNNING" "$STATE_FILE"; then
            echo "⚡ Server was RUNNING before shutdown/power cut. Restoring all SIMS services..."
            "$SCRIPT_DIR/sims-server.sh" start
        else
            echo "💤 Server was STOPPED prior to shutdown. Keeping SIMS services inactive on this boot."
            sudo systemctl stop nginx "$PHP_FPM" redis-server 2>/dev/null || true
            pm2 stop all 2>/dev/null || true
        fi
        ;;

    setup-autoboot)
        echo "⚙️ Setting up State-Aware Auto-Boot Service in systemd..."
        AUTOBOOT_SERVICE="/etc/systemd/system/sims-autoboot.service"
        CURRENT_USER="$(id -un)"
        sudo bash -c "cat <<EOF > $AUTOBOOT_SERVICE
[Unit]
Description=SIMS State-Aware Auto-Boot Recovery Manager
After=network.target

[Service]
Type=oneshot
User=$CURRENT_USER
ExecStart=$SCRIPT_DIR/sims-server.sh auto-boot
RemainAfterExit=true

[Install]
WantedBy=multi-user.target
EOF"
        sudo systemctl daemon-reload
        sudo systemctl enable sims-autoboot.service
        echo "✅ State-Aware Auto-Boot Service is installed and enabled!"
        echo "   - If server was RUNNING before power loss/reboot -> Auto-starts immediately."
        echo "   - If server was STOPPED by you -> Stays off on reboot."
        ;;

    free-ports)
        echo "🧹 Scanning and freeing all SIMS ports (80, 6379, 3000, 3001, 8000)..."
        free_port 80 "Nginx HTTP"
        free_port 6379 "Redis Server"
        free_port 3000 "WhatsApp Microservice (Port 3000)"
        free_port 3001 "WhatsApp Microservice (Port 3001)"
        free_port 8000 "Artisan Serve"
        echo "✨ All SIMS ports are now free and available!"
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
        echo "   sims free-ports"
        echo "   sims setup-autoboot"
        ;;
        
    *)
        echo "Usage: $0 {start|stop|restart|status|free-ports|auto-boot|setup-autoboot|install-cli}"
        exit 1
        ;;
esac
