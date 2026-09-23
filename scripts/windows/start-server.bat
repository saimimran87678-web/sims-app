@echo off
setlocal
cd /d "%~dp0"
title Start SIMS Web Server (HTTPS & HTTP)

net session >nul 2>&1
if %errorLevel% equ 0 goto :ADMIN_OK

echo [ERROR] Administrator privileges are required to bind Web Ports 80 and 443.
echo Please right-click start-server.bat and select "Run as administrator".
echo.
pause
exit /b 1

:ADMIN_OK
echo ====================================================
echo             SIMS Web Server Launcher
echo ====================================================

if exist "%~dp0services\trust-cert.bat" call "%~dp0services\trust-cert.bat"
echo.

call "%~dp0services\run-web.bat"

if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Web server stopped with error code %errorLevel%.
)
echo.
pause
