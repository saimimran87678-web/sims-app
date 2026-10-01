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
if not defined ROOT_CRT if exist "%USERPROFILE%\AppData\Roaming\caddy\pki\authorities\local\root.crt" set "ROOT_CRT=%USERPROFILE%\AppData\Roaming\caddy\pki\authorities\local\root.crt"
if not defined ROOT_CRT if exist "%ProgramData%\caddy\pki\authorities\local\root.crt" set "ROOT_CRT=%ProgramData%\caddy\pki\authorities\local\root.crt"
if not defined ROOT_CRT if exist "%WINDIR%\System32\config\systemprofile\AppData\Roaming\caddy\pki\authorities\local\root.crt" set "ROOT_CRT=%WINDIR%\System32\config\systemprofile\AppData\Roaming\caddy\pki\authorities\local\root.crt"
if not defined ROOT_CRT (
    for /f "delims=" %%F in ('dir /s /b "%APP_DIR%\storage\caddy\root.crt" 2^>nul') do set "ROOT_CRT=%%F"
)

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

:: Export a copy to root folder for easy distribution to other PCs/devices
copy /y "%ROOT_CRT%" "%ROOT_DIR%\sims-ssl-root-cert.crt" >nul 2>&1

:: Generate 1-click installer for client computers on the network
(
    echo @echo off
    echo setlocal
    echo title SIMS Network SSL Certificate Installer
    echo echo ====================================================
    echo echo   SIMS Local Network SSL Certificate Installer
    echo echo ====================================================
    echo echo.
    echo net session ^>nul 2^>^&1
    echo if %%errorLevel%% neq 0 ^(
    echo     echo [ERROR] Administrator privileges are required!
    echo     echo Please right-click this script and select "Run as administrator".
    echo     echo.
    echo     pause
    echo     exit /b 1
    echo ^)
    echo cd /d "%%~dp0"
    echo if not exist "sims-ssl-root-cert.crt" ^(
    echo     echo [ERROR] sims-ssl-root-cert.crt not found!
    echo     echo Please ensure sims-ssl-root-cert.crt is placed next to this script.
    echo     echo.
    echo     pause
    echo     exit /b 1
    echo ^)
    echo echo [INFO] Installing SIMS Root Certificate into Trusted Root store...
    echo certutil.exe -addstore -f "ROOT" "sims-ssl-root-cert.crt" ^>nul 2^>^&1
    echo if %%errorLevel%% equ 0 ^(
    echo     echo.
    echo     echo ====================================================
    echo     echo [OK] Certificate installed successfully!
    echo     echo      This device will now recognize SIMS HTTPS as SECURE.
    echo     echo      NOTE: Please restart your browser ^(close all tabs^) to apply.
    echo     echo ====================================================
    echo ^) else ^(
    echo     certutil.exe -user -addstore -f "ROOT" "sims-ssl-root-cert.crt" ^>nul 2^>^&1
    echo     echo [OK] Certificate installed for current user.
    echo ^)
    echo echo.
    echo pause
) > "%ROOT_DIR%\install-client-cert.bat" 2>nul

if exist "%ROOT_DIR%\sims-ssl-root-cert.crt" (
    echo [EXPORT] Exported SSL Root Certificate and Client Installer:
    echo          "%ROOT_DIR%\sims-ssl-root-cert.crt"
    echo          "%ROOT_DIR%\install-client-cert.bat"
    echo.
)

:: 3. Check for Administrator privileges
net session >nul 2>&1
if %errorLevel% equ 0 (
    echo [INFO] Installing certificate into Windows LocalMachine Root Store...
    certutil.exe -addstore -f "ROOT" "%ROOT_CRT%" >nul 2>&1
    if !errorLevel! equ 0 (
        echo [OK] SSL Certificate successfully trusted on this server PC!
        echo      Local browsers will now recognize HTTPS as SECURE (green padlock).
        echo.
        echo [LOCAL NETWORK TRUST FOR OTHER COMPUTERS]:
        echo   1. Copy "sims-ssl-root-cert.crt" and "install-client-cert.bat" to other PCs
        echo   2. Right-click "install-client-cert.bat" and choose "Run as administrator"
        echo      (Or on any phone/PC, visit http://^<server-ip^>/cert to download it)
        echo.
        echo      NOTE: Please restart your browser (close all tabs) to apply.
        exit /b 0
    )
)

:: 4. If not elevated or machine store failed, install into CurrentUser store
echo [INFO] Installing certificate into Windows CurrentUser Root Store...
certutil.exe -user -addstore -f "ROOT" "%ROOT_CRT%" >nul 2>&1
if %errorLevel% equ 0 (
    echo [OK] SSL Certificate successfully trusted for current user on this server PC!
    echo      Local browsers will now recognize HTTPS as SECURE (green padlock).
    echo.
    echo [LOCAL NETWORK TRUST FOR OTHER COMPUTERS]:
    echo   Copy "sims-ssl-root-cert.crt" and "install-client-cert.bat" to other PCs
    echo   and run "install-client-cert.bat" as Administrator.
    echo.
    echo      NOTE: Please restart your browser (close all tabs) to apply.
    exit /b 0
)

echo [NOTICE] Run this script as Administrator to install the root certificate
echo          machine-wide so browsers recognize HTTPS as SECURE.
exit /b 0
