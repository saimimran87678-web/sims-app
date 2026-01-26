@echo off
echo Starting SIMS Project...

:: Start Laravel Server
start "SIMS App" cmd /k "cd sims-app && php artisan serve"

:: Start WhatsApp Service
start "WhatsApp Service" cmd /k "cd whatsapp-service && node server.js"

echo Services started in new windows.
pause
