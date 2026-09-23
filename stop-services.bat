@echo off
setlocal
cd /d "%~dp0"
title Stop SIMS Services

:: Auto-elevate to Administrator if double-clicked by standard user
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Requesting Administrator privileges to stop background services...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b 0
)

echo ====================================================
echo         Stopping All SIMS Windows Services
echo ====================================================
echo.

echo [1/2] Stopping background scheduled tasks...
schtasks /end /tn "SIMS-Web" >nul 2>&1
schtasks /end /tn "SIMS-Queue" >nul 2>&1
schtasks /end /tn "SIMS-Scheduler" >nul 2>&1

echo [2/2] Terminating any remaining PHP and FrankenPHP processes...
taskkill /F /IM frankenphp.exe /IM php.exe >nul 2>&1

echo.
echo ====================================================
echo  [OK] All SIMS services have been completely stopped.
echo       You can now safely move, edit, or delete files.
echo ====================================================
echo.
pause
