#!/bin/bash

# ============================================
# SIMS Project - Install Dependencies
# Run this AFTER setup-fresh-pc.sh
# ============================================

set -e

echo "=========================================="
echo "📦 Installing SIMS Project Dependencies"
echo "=========================================="

# Navigate to Laravel app
cd ~/SIMS/sims-app

echo ""
echo "Step 1: Installing PHP dependencies (Composer)..."
echo "-------------------------------------------"
composer install --no-dev --optimize-autoloader

echo ""
echo "Step 2: Installing Node dependencies (npm)..."
echo "-------------------------------------------"
npm install

echo ""
echo "Step 3: Setting up environment..."
echo "-------------------------------------------"
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
    echo "Environment file created"
else
    echo "Environment file already exists"
fi

echo ""
echo "Step 4: Building production assets..."
echo "-------------------------------------------"
npm run build

echo ""
echo "Step 5: Setting up WhatsApp service..."
echo "-------------------------------------------"
cd ~/SIMS/whatsapp-service
npm install

echo ""
echo "=========================================="
echo "✅ Project setup complete!"
echo "=========================================="
echo ""
echo "To start the app, run:"
echo "  ~/SIMS/start-sims.sh"
echo ""
echo "Or manually:"
echo "  Terminal 1: cd ~/SIMS/sims-app && php artisan serve"
echo "  Terminal 2: ngrok http --domain=isabella-cherrylike-anomalously.ngrok-free.dev 8000"
echo "  Terminal 3: cd ~/SIMS/whatsapp-service && npm start"
echo ""
