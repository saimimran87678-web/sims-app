@echo off
setlocal
cd /d "%~dp0"
title Stop SIMS Services

net session >nul 2>&1
if %errorLevel% equ 0 goto :ADMIN_OK

echo [ERROR] Administrator privileges are required to stop background services.
echo Please right-click stop-services.bat and select "Run as administrator".
echo.
pause
exit /b 1

:ADMIN_OK
echo ====================================================
echo         Stopping All SIMS Windows Services
echo ====================================================
echo.

echo [1/2] Stopping background scheduled tasks...
schtasks /end /tn "SIMS-Web" >nul 2>&1
schtasks /end /tn "SIMS-Queue" >nul 2>&1
schtasks /end /tn "SIMS-Scheduler" >nul 2>&1

echo [2/2] Terminating any remaining PHP and FrankenPHP processes...
taskkill /F /IM frankenphp.exe /IM php.exe /IM php-cgi.exe >nul 2>&1

echo.
echo ====================================================
echo  [OK] All SIMS services have been completely stopped.
echo       You can now safely move, edit, or delete files.
echo ====================================================
echo.
pause
