# SIMS Institute Portal — Production Installation & Environment Setup Guide

This guide provides an optimized, production-accurate checklist and deployment workflow for the **SIMS Institute Portal**. All dependencies, health-check commands, missing tool installation procedures, and operational scripts are consolidated under a single, unified reference.

---

## 📋 Table of Contents
1. [Core Application Stack](#1-core-application-stack)
2. [Master Dependency Matrix ("Under a Single Hood")](#2-master-dependency-matrix-under-a-single-hood)
3. [One-Command Automated Dependency Audit](#3-one-command-automated-dependency-audit)
4. [Step-by-Step Installation of Missing Components](#4-step-by-step-installation-of-missing-components)
5. [SIMS Application Setup & Deployment](#5-sims-application-setup--deployment)
6. [Global CLI Management (`sims` Command)](#6-global-cli-management-sims-command)
7. [Operational Verification & Troubleshooting](#7-operational-verification--troubleshooting)

---

## 1. Core Application Stack

The SIMS Institute Portal is built for Linux production environments (Ubuntu / Debian / Linux Mint) with the following architecture:

* **Backend Framework:** Laravel 12 on PHP 8.3 CLI & PHP-FPM
* **Frontend Engine:** Livewire 3 + Alpine.js + Tailwind CSS (bundled via Vite)
* **Web Server & Reverse Proxy:** Nginx (FastCGI passing to PHP-FPM)
* **Database:** SQLite 3 (`database/database.sqlite`)
* **In-Memory Cache & Queues:** Redis Server + `php8.3-redis` extension
* **Process Supervisor:** PM2 Daemon (manages Laravel queue workers & task scheduler)

---

## 2. Master Dependency Matrix ("Under a Single Hood")

Below is the complete list of all system tools, language runtimes, web servers, daemons, and PHP extensions required by SIMS.

> [!CAUTION]
> **Strict Version-Pinning Rule:**
> **NEVER** run generic commands like `sudo apt install php` or `sudo apt install php-redis`. Generic unversioned commands can pull in wrong default versions (e.g. PHP 8.1), install Apache2 by accident, or create conflicting socket configurations. Always specify the exact version (e.g., `php8.3`, `php8.3-fpm`, `php8.3-redis`).
>
> *(Note for other PHP versions: If your server intentionally uses PHP 8.2, simply replace `8.3` with `8.2` across all commands and package names.)*

| # | Component / Service | Purpose in SIMS | Target Version | How to Check / Test | How to Install if Missing |
| :-: | :--- | :--- | :--- | :--- | :--- |
| **1** | **PHP CLI** | Executes Artisan commands, migrations, and CLI scripts | `8.3.x` | `php -v` | `sudo apt install -y php8.3 php8.3-cli` |
| **2** | **PHP-FPM** | FastCGI Process Manager serving web requests to Nginx | `8.3.x` | `systemctl is-active php8.3-fpm` | `sudo apt install -y php8.3-fpm && sudo systemctl enable --now php8.3-fpm` |
| **3** | **PHP Redis Extension** | High-performance binary driver for Redis session & cache (`phpredis`) | Compatible with PHP 8.3 | `php -m \| grep -i redis` | `sudo apt install -y php8.3-redis && sudo systemctl restart php8.3-fpm` |
| **4** | **PHP Core Extensions** | SQLite database driver, DOMPDF generator, cURL API client, string & math ops | Compatible with PHP 8.3 | `php -m \| grep -E 'bcmath\|curl\|gd\|intl\|mbstring\|pdo_sqlite\|sqlite3\|xml\|zip'` | `sudo apt install -y php8.3-bcmath php8.3-curl php8.3-gd php8.3-intl php8.3-mbstring php8.3-sqlite3 php8.3-xml php8.3-zip` |
| **5** | **Composer** | PHP dependency and autoloader manager | `2.x` or higher | `composer --version` | `curl -sS https://getcomposer.org/installer \| php && sudo mv composer.phar /usr/local/bin/composer && sudo chmod +x /usr/local/bin/composer` |
| **6** | **Node.js & NPM** | Vite asset bundler and JavaScript microservices | `20.x` LTS | `node -v && npm -v` | `curl -fsSL https://deb.nodesource.com/setup_20.x \| sudo -E bash - && sudo apt install -y nodejs` |
| **7** | **Nginx Web Server** | Production HTTP reverse proxy, SSL termination, and static asset serving | `1.18+` | `nginx -v && systemctl is-active nginx` | `sudo apt install -y nginx && sudo systemctl enable --now nginx` |
| **8** | **PM2 Process Manager** | Production daemon supervisor running background queues and scheduler | `Latest` | `pm2 -v` | `sudo npm install -g pm2` |
| **9** | **Redis Server** | In-memory RAM database for sub-millisecond sessions, cache, and queue jobs | `6.x` or `7.x` | `redis-cli ping` *(Expect `PONG`)* | `sudo apt install -y redis-server && sudo systemctl enable --now redis-server` |
| **10** | **SQLite 3 Engine** | Embedded database engine used by SIMS | `3.35+` | `sqlite3 --version` | `sudo apt install -y sqlite3 libsqlite3-dev` |
| **11** | **Git & Base Utilities** | Source control management and archive extraction | `2.x+` | `git --version && curl --version && unzip -v` | `sudo apt install -y git curl unzip software-properties-common` |

---

## 3. One-Command Automated Dependency Audit

Before installing anything manually, copy and run this single audit command in your terminal. It inspects all 11 required components at once and reports their real-time availability:

```bash
echo "=================================================="
echo "      🔍 SIMS SYSTEM DEPENDENCY AUDIT CHECK       "
echo "=================================================="
PHP_V=$(command -v php >/dev/null 2>&1 && php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' || echo "none")

# 1. PHP & PHP-FPM
command -v php >/dev/null 2>&1 && echo "✅ PHP CLI: Installed ($(php -v | head -n1 | cut -d' ' -f2))" || echo "❌ PHP CLI: NOT installed"
systemctl is-active --quiet "php${PHP_V}-fpm" 2>/dev/null && echo "✅ PHP-FPM (php${PHP_V}-fpm): ACTIVE" || echo "❌ PHP-FPM (php${PHP_V}-fpm): NOT active / installed"

# 2. PHP Redis Extension
php -m 2>/dev/null | grep -qi "redis" && echo "✅ PHP Redis Extension (php${PHP_V}-redis): LOADED" || echo "❌ PHP Redis Extension: NOT loaded (Run: sudo apt install -y php${PHP_V}-redis)"

# 3. PHP Required Modules
MISSING_MODS=""
for mod in bcmath curl gd intl mbstring pdo_sqlite sqlite3 xml zip; do
    php -m 2>/dev/null | grep -qi "^$mod$" || MISSING_MODS="$MISSING_MODS php${PHP_V}-$mod"
done
[ -z "$MISSING_MODS" ] && echo "✅ PHP Core Extensions: All required modules loaded" || echo "❌ PHP Core Extensions: Missing ($MISSING_MODS)"

# 4. Toolchain & Daemons
command -v composer >/dev/null 2>&1 && echo "✅ Composer: Installed ($(composer --version 2>/dev/null | head -n1 | cut -d' ' -f3))" || echo "❌ Composer: NOT installed"
command -v node >/dev/null 2>&1 && echo "✅ Node.js: Installed ($(node -v))" || echo "❌ Node.js: NOT installed"
command -v npm >/dev/null 2>&1 && echo "✅ NPM: Installed ($(npm -v))" || echo "❌ NPM: NOT installed"
command -v pm2 >/dev/null 2>&1 && echo "✅ PM2: Installed ($(pm2 -v))" || echo "❌ PM2: NOT installed"
command -v nginx >/dev/null 2>&1 && echo "✅ Nginx: Installed ($(nginx -v 2>&1 | cut -d'/' -f2))" || echo "❌ Nginx: NOT installed"
command -v sqlite3 >/dev/null 2>&1 && echo "✅ SQLite 3: Installed ($(sqlite3 --version | cut -d' ' -f1))" || echo "❌ SQLite 3: NOT installed"
redis-cli ping 2>/dev/null | grep -q "PONG" && echo "✅ Redis Server: ACTIVE (Responded PONG)" || echo "❌ Redis Server: NOT responding / installed"
echo "=================================================="
```

---

## 4. Step-by-Step Installation of Missing Components

If any items showed ❌ in the audit above, run the corresponding command block below on Ubuntu / Debian / Linux Mint:

### Step 4.1: Update Repositories & Base Utilities
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget unzip software-properties-common ca-certificates gnupg
```

### Step 4.2: Add PHP PPA & Install Version-Pinned PHP 8.3 Stack
```bash
# Add official Ondrej PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 CLI, PHP 8.3-FPM, PHP 8.3 Redis Extension, and required extensions
# (If using PHP 8.2 instead, replace '8.3' with '8.2' throughout)
sudo apt install -y \
    php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-redis \
    php8.3-sqlite3 \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-intl

# Enable and start PHP-FPM service
sudo systemctl enable --now php8.3-fpm
```

### Step 4.3: Install Composer (Global PHP Package Manager)
```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
composer --version
```

### Step 4.4: Install Node.js 20 LTS & PM2
```bash
# Install NodeSource repository for Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install PM2 globally to supervise background workers
sudo npm install -g pm2
```

### Step 4.5: Install Nginx Web Server
```bash
sudo apt install -y nginx
sudo systemctl enable --now nginx
```

### Step 4.6: Install & Start Redis Server
```bash
sudo apt install -y redis-server
sudo systemctl enable --now redis-server

# Verify Redis is functioning
redis-cli ping
# Output should be: PONG
```

---

## 5. SIMS Application Setup & Deployment

Once all prerequisites from Section 2 are installed and verified, deploy the SIMS application:

### Step 1: Navigate to Application Directory
```bash
cd /home/saim/SIMS/sims-app
```

### Step 2: Configure Environment File (`.env`)
```bash
# Create .env from template if not already present
[ -f .env ] || cp .env.example .env

# Generate application encryption key
php artisan key:generate
```

Open `.env` and verify the key database and cache settings:
```env
APP_NAME="SIMS Institute Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# Database Configuration (SQLite)
DB_CONNECTION=sqlite
DB_FOREIGN_KEYS=true

# High-Performance Cache & Session Store
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# SIMS License Credentials (supplied by vendor)
FIREBASE_API_KEY="your-firebase-api-key"
FIREBASE_PROJECT_ID="your-firebase-project-id"
LICENSE_KEY="your-license-key"
LICENSE_RSA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n..."
```

### Step 3: Install Dependencies
```bash
# Install PHP vendor packages (optimized for production)
composer install --no-dev --optimize-autoloader

# Install Node frontend dependencies
npm install
```

### Step 4: Initialize Database & Build Assets
```bash
# Create SQLite database file if missing
touch database/database.sqlite
chmod 0666 database/database.sqlite
chmod 0777 database

# Run database migrations
php artisan migrate --force

# Create public storage symlink
php artisan storage:link

# Compile production CSS/JS assets via Vite
npm run build
```

### Step 5: Configure Nginx Web Server
Link the production Nginx virtual host configuration included in `deployment/nginx/sims.conf`:
```bash
# Copy or symlink virtual host configuration
sudo cp deployment/nginx/sims.conf /etc/nginx/sites-available/sims.conf
sudo ln -sf /etc/nginx/sites-available/sims.conf /etc/nginx/sites-enabled/

# Remove default Nginx welcome page if active
sudo rm -f /etc/nginx/sites-enabled/default

# Test configuration syntax and reload Nginx
sudo nginx -t
sudo systemctl reload nginx
```

### Step 6: Start Background Workers with PM2
Launch the Laravel Queue Worker and Scheduler defined in `ecosystem.config.cjs`:
```bash
# Start queue workers and task scheduler
pm2 start ecosystem.config.cjs

# Save PM2 state and configure auto-start on system boot
pm2 save
pm2 startup
```

---

## 6. Global CLI Management (`sims` Command)

To make server administration seamless without memorizing complex system commands, link `sims-server.sh` as a global command in `/usr/local/bin/sims`.

### Enable the Global `sims` Command:
Run this single command from inside `sims-app`:
```bash
./sims-server.sh install-cli
```
*(Or manually: `sudo ln -sf /home/saim/SIMS/sims-app/sims-server.sh /usr/local/bin/sims && sudo chmod +x /home/saim/SIMS/sims-app/sims-server.sh`)*

### Controlling the Server from Any Terminal Directory:

| Command | Action Performed |
| :--- | :--- |
| **`sims status`** | Displays the real-time health dashboard for Nginx, PHP-FPM, Redis, PHP Redis extension, and active PM2 workers. |
| **`sims start`** | Starts Redis, PHP-FPM, and Nginx systemd services, and boots PM2 background workers. |
| **`sims restart`** | Restarts all services, restarts PM2 workers, and automatically runs `php artisan app:optimize` (caches routes/views/config, publishes Livewire scripts, and resets file permissions). |
| **`sims stop`** | Gracefully shuts down PM2 queue workers and stops system services. |

---

## 7. Operational Verification & Troubleshooting

### 1. Test PHP Redis Connection
```bash
php -r "
try {
    \$redis = new Redis();
    \$redis->connect('127.0.0.1', 6379);
    echo '✅ Redis connection successful: ' . \$redis->ping('PONG') . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Redis connection failed: ' . \$e->getMessage() . PHP_EOL;
}
"
```

### 2. Verify Nginx Syntax & Status
```bash
sudo nginx -t
systemctl status nginx
```

### 3. Inspect Live PM2 Worker Logs
```bash
pm2 logs
pm2 logs sims-queue
pm2 logs sims-scheduler
```

### 4. Clear and Rebuild All Application Caches
If you make modifications to `.env` or configuration files:
```bash
sims restart
# OR directly via Artisan:
php artisan optimize:clear
php artisan app:optimize
```
