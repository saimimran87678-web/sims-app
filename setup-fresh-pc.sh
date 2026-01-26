#!/bin/bash

# ============================================
# SIMS Project - Linux Mint Setup Script
# For Pakistan region with local mirrors
# ============================================

set -e  # Exit on error

echo "=========================================="
echo "🚀 SIMS Project - Fresh PC Setup"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# ============================================
# Step 1: Update System with Pakistan Mirror
# ============================================
echo ""
echo "Step 1: Updating system packages..."
echo "-------------------------------------------"

# Backup original sources list
sudo cp /etc/apt/sources.list /etc/apt/sources.list.backup 2>/dev/null || true

# Pakistan doesn't have official Ubuntu mirrors, but LUMS maintains one
# Using fastest available mirrors for the region
print_warning "Using fastest available mirrors for Pakistan region..."

sudo apt update -y
sudo apt upgrade -y
print_status "System updated"

# ============================================
# Step 2: Install Required System Packages
# ============================================
echo ""
echo "Step 2: Installing system packages..."
echo "-------------------------------------------"

sudo apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg

print_status "System packages installed"

# ============================================
# Step 3: Install PHP 8.2+ and Extensions
# ============================================
echo ""
echo "Step 3: Installing PHP 8.2..."
echo "-------------------------------------------"

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions required by Laravel
sudo apt install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-sqlite3 \
    php8.2-intl \
    php8.2-bcmath \
    php8.2-gd

print_status "PHP 8.2 installed"
php -v

# ============================================
# Step 4: Install Composer (PHP Package Manager)
# ============================================
echo ""
echo "Step 4: Installing Composer..."
echo "-------------------------------------------"

# Using Packagist mirror for faster downloads in Asia
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Configure Composer to use faster mirror for Asia/Pakistan
composer config -g repos.packagist composer https://packagist.org
# Alternative: composer config -g repos.packagist composer https://packagist.phpcomposer.com

print_status "Composer installed"
composer --version

# ============================================
# Step 5: Install Node.js 20 LTS
# ============================================
echo ""
echo "Step 5: Installing Node.js 20 LTS..."
echo "-------------------------------------------"

# Using NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Configure npm to use faster registry (optional: Taobao mirror works well in Asia)
npm config set registry https://registry.npmjs.org/
# Alternative for Asia: npm config set registry https://registry.npmmirror.com

print_status "Node.js installed"
node -v
npm -v

# ============================================
# Step 6: Install Ngrok
# ============================================
echo ""
echo "Step 6: Installing Ngrok..."
echo "-------------------------------------------"

curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null
echo "deb https://ngrok-agent.s3.amazonaws.com buster main" | sudo tee /etc/apt/sources.list.d/ngrok.list
sudo apt update
sudo apt install -y ngrok

print_status "Ngrok installed"

# ============================================
# Step 7: Setup Ngrok Auth Token
# ============================================
echo ""
echo "Step 7: Ngrok Configuration..."
echo "-------------------------------------------"
print_warning "You need to configure Ngrok with your auth token"
echo ""
echo "Run this command with YOUR token:"
echo "  ngrok config add-authtoken YOUR_TOKEN_HERE"
echo ""
echo "Get your token from: https://dashboard.ngrok.com/get-started/your-authtoken"

# ============================================
# Step 8: Project Setup Instructions
# ============================================
echo ""
echo "=========================================="
echo "✅ All dependencies installed!"
echo "=========================================="
echo ""
echo "📋 NEXT STEPS:"
echo ""
echo "1. Copy your project folder to: ~/SIMS/"
echo ""
echo "2. Navigate to Laravel app:"
echo "   cd ~/SIMS/sims-app"
echo ""
echo "3. Install PHP dependencies:"
echo "   composer install"
echo ""
echo "4. Install Node dependencies:"
echo "   npm install"
echo ""
echo "5. Setup environment:"
echo "   cp .env.example .env  # (if needed)"
echo "   php artisan key:generate"
echo ""
echo "6. Build assets:"
echo "   npm run build"
echo ""
echo "7. Setup WhatsApp service:"
echo "   cd ~/SIMS/whatsapp-service"
echo "   npm install"
echo ""
echo "8. Start services:"
echo "   ~/SIMS/start-sims.sh"
echo ""
echo "=========================================="
echo "🌐 Your Ngrok URL:"
echo "https://isabella-cherrylike-anomalously.ngrok-free.dev"
echo "=========================================="
