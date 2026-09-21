@echo off
setlocal enabledelayedexpansion
title SIMS Automated System Installer

echo ====================================================
echo      🏫 SIMS School Management System Installer     
echo ====================================================
echo.

:: 1. Check for Administrator Privileges
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Administrator privileges are required to configure Port 80
    echo         and register background system services.
    echo.
    echo Please right-click "install.bat" and select "Run as administrator".
    echo ====================================================
    pause
    exit /b 1
)

:: 2. Resolve script and runtime directories
set "ROOT_DIR=%~dp0"
set "APP_DIR=%ROOT_DIR%sims-app"
set "PORTABLE_PHP=%ROOT_DIR%runtime\php\php.exe"

if exist "%PORTABLE_PHP%" (
    set "PHP_BIN=%PORTABLE_PHP%"
    echo [OK] Using bundled portable PHP runtime.
) else (
    where php >nul 2>&1
    if %errorLevel% equ 0 (
        set "PHP_BIN=php"
        echo [OK] Using system PHP runtime.
    ) else (
        echo [ERROR] PHP runtime not found!
        echo Please ensure runtime\php\php.exe exists or install PHP 8.2+.
        pause
        exit /b 1
    )
)

echo.
echo [1/3] Configuring Port 80 HTTP access permissions...
netsh http add urlacl url=http://+:80/ user=Everyone >nul 2>&1
echo [OK] Port 80 access granted.

echo.
echo [2/3] Performing initial database and cache setup...
cd /d "%APP_DIR%"
"%PHP_BIN%" artisan sims:install
if %errorLevel% neq 0 (
    echo [ERROR] Initial installation failed.
    pause
    exit /b 1
)

echo.
echo [3/3] Registering background services in Windows Task Scheduler...
"%PHP_BIN%" artisan sims:setup-windows
if %errorLevel% neq 0 (
    echo [WARNING] Background service registration reported a warning.
)

echo.
echo ====================================================
echo  🎉 SIMS Installation & Service Setup Complete!     
echo ====================================================
echo.
echo Opening SIMS in your default web browser...
start http://localhost

echo.
echo Teachers and staff can access SIMS on the local school network at:
echo   http://%COMPUTERNAME%
echo.
pause
