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
set "BIN_DIR=%ROOT_DIR%bin"
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

:: 3. Check for Port 80 Conflicts (e.g. Windows IIS / W3SVC)
echo.
echo [1/4] Checking and configuring Port 80 HTTP access...
sc query W3SVC >nul 2>&1
if %errorLevel% equ 0 (
    sc query W3SVC | findstr "RUNNING" >nul 2>&1
    if !errorLevel! equ 0 (
        echo [NOTICE] Windows IIS (W3SVC) is currently active on Port 80.
        echo [INFO] Stopping IIS to free up Port 80 for SIMS...
        net stop W3SVC >nul 2>&1
        sc config W3SVC start=disabled >nul 2>&1
    )
)
netsh http add urlacl url=http://+:80/ user=Everyone >nul 2>&1
echo [OK] Port 80 access granted.

:: 4. Database, migrations, and cache warming
echo.
echo [2/4] Performing initial database and cache setup...
cd /d "%APP_DIR%"
"%PHP_BIN%" artisan sims:install
if %errorLevel% neq 0 (
    echo [ERROR] Initial installation failed.
    pause
    exit /b 1
)

:: 5. Register background services via Task Scheduler (pointing to bin/*.bat runners)
echo.
echo [3/4] Registering background services in Windows Task Scheduler...
"%PHP_BIN%" artisan sims:setup-windows
if %errorLevel% neq 0 (
    echo [WARNING] Background service registration reported a warning.
)

:: 6. Launch services and verify Port 80 is listening before opening browser
echo.
echo [4/4] Starting SIMS Web Server and verifying Port 80...
schtasks /run /tn "SIMS-Web" >nul 2>&1
schtasks /run /tn "SIMS-Queue" >nul 2>&1
schtasks /run /tn "SIMS-Scheduler" >nul 2>&1

set "SERVER_UP=0"
for /l %%i in (1,1,6) do (
    netstat -ano | findstr /R ":80[ ]" >nul 2>&1
    if !errorLevel! equ 0 (
        set "SERVER_UP=1"
        goto :SERVER_ONLINE
    )
    timeout /t 1 /nobreak >nul
)

:SERVER_ONLINE
if !SERVER_UP! equ 1 (
    echo [OK] SIMS Web Server is ONLINE and listening on Port 80.
) else (
    echo [INFO] Starting web server directly in background...
    if exist "%BIN_DIR%\run-web.bat" (
        start "" /min "%BIN_DIR%\run-web.bat"
        timeout /t 3 /nobreak >nul
    )
)

echo.
echo ====================================================
echo  🎉 SIMS Installation and Service Setup Complete!     
echo ====================================================
echo.
echo Opening SIMS in your default web browser...
start http://localhost

echo.
echo Teachers and staff can access SIMS on the local school network at:
echo   http://%COMPUTERNAME%
echo.
pause
