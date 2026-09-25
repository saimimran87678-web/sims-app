@echo off
setlocal enabledelayedexpansion

:: 1. Resolve Root and App Directories (3 levels up from scripts\windows\services\)
for %%I in ("%~dp0..\..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"
set "APP_DIR=%ROOT_DIR%\sims-app"

echo ====================================================
echo      SIMS Local SSL Certificate Trust Manager
echo ====================================================

:: 2. Locate Caddy Root CA Certificate (with retry loop for cold starts)
set "ROOT_CRT="
set "CRT_TRIES=0"

:CHECK_CRT_LOOP
if exist "%APP_DIR%\storage\caddy\pki\authorities\local\root.crt" set "ROOT_CRT=%APP_DIR%\storage\caddy\pki\authorities\local\root.crt"
if not defined ROOT_CRT if exist "%APPDATA%\caddy\pki\authorities\local\root.crt" set "ROOT_CRT=%APPDATA%\caddy\pki\authorities\local\root.crt"
if not defined ROOT_CRT if exist "%LOCALAPPDATA%\caddy\pki\authorities\local\root.crt" set "ROOT_CRT=%LOCALAPPDATA%\caddy\pki\authorities\local\root.crt"
if not defined ROOT_CRT if exist "%WINDIR%\System32\config\systemprofile\AppData\Roaming\caddy\pki\authorities\local\root.crt" set "ROOT_CRT=%WINDIR%\System32\config\systemprofile\AppData\Roaming\caddy\pki\authorities\local\root.crt"

if defined ROOT_CRT goto :FOUND_CRT

set /a CRT_TRIES+=1
if %CRT_TRIES% lss 6 (
    ping 127.0.0.1 -n 2 >nul
    goto :CHECK_CRT_LOOP
)

echo [NOTICE] Caddy Root CA certificate has not been generated yet.
echo          It will be automatically installed once the web server runs for the first time.
exit /b 0

:FOUND_CRT

echo [INFO] Found Caddy Root Certificate:
echo        "%ROOT_CRT%"
echo.

:: 3. Check for Administrator privileges
net session >nul 2>&1
if %errorLevel% equ 0 (
    echo [INFO] Installing certificate into Windows LocalMachine Root Store...
    certutil.exe -addstore -f "ROOT" "%ROOT_CRT%" >nul 2>&1
    if !errorLevel! equ 0 (
        echo [OK] SSL Certificate successfully trusted machine-wide!
        echo      Your browser will now recognize https://localhost as SECURE.
        exit /b 0
    )
)

:: 4. If not elevated or machine store failed, install into CurrentUser store
echo [INFO] Installing certificate into Windows CurrentUser Root Store...
certutil.exe -user -addstore -f "ROOT" "%ROOT_CRT%" >nul 2>&1
if %errorLevel% equ 0 (
    echo [OK] SSL Certificate successfully trusted for current user!
    echo      Your browser will now recognize https://localhost as SECURE.
    exit /b 0
)

echo [NOTICE] Run this script as Administrator to install the root certificate
echo          machine-wide so all browsers recognize https://localhost as SECURE.
exit /b 0
