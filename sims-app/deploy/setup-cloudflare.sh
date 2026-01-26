#!/bin/bash

# setup-cloudflare.sh
# Script to install Cloudflare Tunnel (cloudflared) on Linux Mint / Ubuntu

set -e

echo ">>> Checking for cloudflared..."

if command -v cloudflared &> /dev/null; then
    echo "✅ cloudflared is already installed."
else
    echo "⚠️ cloudflared not found. Installing..."
    
    # Add Cloudflare GPG key
    sudo mkdir -p --mode=0755 /usr/share/keyrings
    curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg | sudo tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null

    # Add Cloudflare repo
    echo 'deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared jammy main' | sudo tee /etc/apt/sources.list.d/cloudflared.list

    # Update and install
    sudo apt-get update && sudo apt-get install cloudflared -y
    
    echo "✅ cloudflared installed successfully!"
fi

echo ""
echo ">>> Setup Complete!"
echo "To start a quick tunnel, run:"
echo "cloudflared tunnel --url http://localhost:8000"
echo ""
echo "For more details, see deploy/CLOUDFLARE_GUIDE.md"
