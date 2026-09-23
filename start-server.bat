@echo off
setlocal
cd /d "%~dp0"
title Start SIMS Web Server (HTTPS & HTTP)

:: Check for Administrator Privileges (Ports 80 & 443 require Admin)
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Requesting Administrator privileges to bind Web Ports (80 / 443)...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b 0
)

echo ====================================================
echo             SIMS Web Server Launcher
echo ====================================================
call "%~dp0bin\run-web.bat"

if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Web server stopped with error code %errorLevel%.
)
echo.
pause
