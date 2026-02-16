#!/bin/bash

# SIMS Startup Script
# Double-click this file or run: ./start-sims.sh

echo "🚀 Starting SIMS Services..."

# Start WhatsApp Service
cd /home/saim/SIMS/whatsapp-service
gnome-terminal --tab --title="WhatsApp" -- bash -c "npm start; exec bash" &

sleep 2

# Start Laravel Server
cd /home/saim/SIMS/sims-app
gnome-terminal --tab --title="Laravel" -- bash -c "php artisan serve; exec bash" &

sleep 2

# Start Ngrok Tunnel
gnome-terminal --tab --title="Ngrok" -- bash -c "ngrok http --domain=isabella-cherrylike-anomalously.ngrok-free.dev 8000; exec bash" &

echo "✅ All services starting!"
echo "📱 Access: https://isabella-cherrylike-anomalously.ngrok-free.dev"
