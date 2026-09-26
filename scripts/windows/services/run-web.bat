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

set "PATH=%RUNTIME_DIR%\php;%RUNTIME_DIR%;%PATH%"

if not exist "%APP_DIR%\storage\logs" mkdir "%APP_DIR%\storage\logs" >nul 2>&1

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

:: 2. Start bundled PHP FastCGI Engine on port 9000 if not already running
set "PHP_CGI=%RUNTIME_DIR%\php\php-cgi.exe"
set "PHP_INI=%RUNTIME_DIR%\php\php.ini"
set "PHP_FCGI_MAX_REQUESTS=0"

if exist "%PHP_CGI%" (
    netstat -ano 2>nul | findstr "127.0.0.1:9000 " >nul 2>&1
    if %errorLevel% neq 0 (
        echo [INFO] Starting bundled PHP 8.2 FastCGI Engine on port 9000...
        echo [%date% %time%] Spawning php-cgi.exe on port 9000... >> "%LOG_FILE%"
        start /b "" "%PHP_CGI%" -b 127.0.0.1:9000 -c "%PHP_INI%"
        ping 127.0.0.1 -n 2 >nul
    ) else (
        echo [OK] PHP FastCGI Engine is already listening on port 9000.
    )
)

:: Run trust certificate in background non-blocking
if exist "%SERVICES_DIR%\trust-cert.bat" (
    start "" /b cmd.exe /c "%SERVICES_DIR%\trust-cert.bat" >nul 2>&1
)

echo.
echo [INFO] Starting Web Server with Caddyfile (HTTPS :443 & HTTP :80)...
echo [URL] HTTP  (Direct) : http://localhost
echo [URL] HTTPS (Secure) : https://localhost
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
