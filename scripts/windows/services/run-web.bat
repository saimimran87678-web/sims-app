@echo off
setlocal enabledelayedexpansion

:: 1. Resolve Root Directory cleanly (3 levels up from scripts\windows\services\)
for %%I in ("%~dp0..\..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "SERVICES_DIR=%~dp0"
if "%SERVICES_DIR:~-1%"=="\" set "SERVICES_DIR=%SERVICES_DIR:~0,-1%"

set "APP_DIR=%ROOT_DIR%\sims-app"
set "APP_PUBLIC=%APP_DIR%\public"
set "RUNTIME_DIR=%ROOT_DIR%\runtime"
set "LOG_FILE=%APP_DIR%\storage\logs\web-server.log"
set "CADDY_DATA_DIR=%APP_DIR%\storage\caddy"
set "CADDY_CONFIG_DIR=%APP_DIR%\storage\caddy\config"

set "PATH=%RUNTIME_DIR%\php;%RUNTIME_DIR%;%PATH%"

if not exist "%APP_DIR%\storage\logs" mkdir "%APP_DIR%\storage\logs" >nul 2>&1
if not exist "%APP_DIR%\storage\caddy" mkdir "%APP_DIR%\storage\caddy" >nul 2>&1

cd /d "%APP_DIR%"

if exist "%RUNTIME_DIR%\frankenphp.exe" (
    set "FRANKEN=%RUNTIME_DIR%\frankenphp.exe"
) else (
    set "FRANKEN=frankenphp.exe"
)

echo [%date% %time%] Starting SIMS Web Server service... >> "%LOG_FILE%"
echo ====================================================
echo   Starting SIMS Web Server (Caddy + PHP 8.2 FastCGI)
echo ====================================================
echo Working Directory: %APP_DIR%
echo Public Root:       %APP_PUBLIC%
echo Web Server:        %FRANKEN%
echo Logging to:        %LOG_FILE%
echo.

:: 2. Start bundled PHP FastCGI Engine pool (ports 9000-9001) if not already running
set "PHP_CGI=%RUNTIME_DIR%\php\php-cgi.exe"
set "PHP_INI=%RUNTIME_DIR%\php\php.ini"
set "PHP_FCGI_MAX_REQUESTS=0"

:: Sync application php.ini into runtime directory if updated by patch
if exist "%APP_DIR%\php.ini" (
    copy /y "%APP_DIR%\php.ini" "%PHP_INI%" >nul 2>&1
)

if exist "%PHP_CGI%" (
    for %%P in (9000 9001) do (
        netstat -ano 2>nul | findstr "127.0.0.1:%%P " >nul 2>&1
        if !errorLevel! neq 0 (
            echo [INFO] Starting bundled PHP 8.2 FastCGI Engine on port %%P...
            echo [%date% %time%] Spawning php-cgi.exe on port %%P... >> "%LOG_FILE%"
            start /b "" "%PHP_CGI%" -b 127.0.0.1:%%P -c "%PHP_INI%"
        ) else (
            echo [OK] PHP FastCGI Engine is already listening on port %%P.
        )
    )
    ping 127.0.0.1 -n 2 >nul
)

:: Run trust certificate in background non-blocking
if exist "%SERVICES_DIR%\trust-cert.bat" (
    start "" /b cmd.exe /c "%SERVICES_DIR%\trust-cert.bat" >nul 2>&1
)

:: 3. Detect active LAN IPv4 addresses and generate local network TLS configuration
set "DETECTED_IPS="
for /f "usebackq delims=" %%I in (`powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.InterfaceAlias -notlike '*Loopback*' -and $_.IPAddress -notlike '169.254*' -and $_.IPAddress -notlike '127*' }).IPAddress" 2^>nul`) do (
    if defined DETECTED_IPS (
        set "DETECTED_IPS=!DETECTED_IPS!, %%I"
    ) else (
        set "DETECTED_IPS=%%I"
    )
)

set "HOSTS_LIST=localhost, 127.0.0.1, sims.local"
if defined COMPUTERNAME (
    set "HOSTS_LIST=!HOSTS_LIST!, %COMPUTERNAME%, %COMPUTERNAME%.local"
)
if defined DETECTED_IPS (
    set "HOSTS_LIST=!HOSTS_LIST!, !DETECTED_IPS!"
)

(
    echo # Auto-generated local network HTTPS configuration
    echo !HOSTS_LIST! {
    echo     import sims_common
    echo     tls internal
    echo }
) > "%CADDY_DATA_DIR%\lan_hosts.caddy"

echo.
echo [INFO] Starting Web Server with Caddyfile (HTTPS :443 & HTTP :80)...
echo [URL] Localhost (HTTP)  : http://localhost
echo [URL] Localhost (HTTPS) : https://localhost
if defined DETECTED_IPS (
    for %%A in (!DETECTED_IPS!) do (
        echo [URL] LAN Access (HTTPS): https://%%A
        echo [URL] LAN Access (HTTP) : http://%%A
    )
)
if defined COMPUTERNAME (
    echo [URL] Machine Name      : https://%COMPUTERNAME%.local
)
echo.

where "%FRANKEN%" >nul 2>&1
if %errorLevel% neq 0 (
    if not exist "%FRANKEN%" (
        echo [WARNING] FrankenPHP not found at "%FRANKEN%". Falling back to PHP built-in server...
        goto :FALLBACK_PHP_SERVER
    )
)

echo [%date% %time%] Launching FrankenPHP with Caddyfile... >> "%LOG_FILE%"
:: Note: --adapter flag is NOT needed; FrankenPHP auto-detects the Caddyfile format.
:: cd /d APP_DIR (line 20) ensures "root * public" in Caddyfile resolves to sims-app\public.
"%FRANKEN%" run --config "%APP_DIR%\Caddyfile" >> "%LOG_FILE%" 2>&1
set "FRANKEN_EXIT=%errorLevel%"
echo [%date% %time%] FrankenPHP exited with code %FRANKEN_EXIT%. >> "%LOG_FILE%"

if %FRANKEN_EXIT% equ 0 goto :RUN_EXIT

:FALLBACK_PHP_SERVER
echo.
echo ====================================================
echo [FALLBACK] Starting PHP Built-in Server on Port 8000
echo ====================================================
echo [URL] Fallback Access: http://localhost:8000
echo.

set "PHP_EXE=%RUNTIME_DIR%\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

echo [%date% %time%] Starting PHP built-in server on 0.0.0.0:8000... >> "%LOG_FILE%"
"%PHP_EXE%" -S 0.0.0.0:8000 -t "%APP_PUBLIC%" >> "%LOG_FILE%" 2>&1

:RUN_EXIT
echo.
echo ====================================================
echo Web server process has stopped.
echo ====================================================
if /i "%~1"=="--interactive" pause
