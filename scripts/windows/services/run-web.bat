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

cd /d "%APP_DIR%"

if exist "%RUNTIME_DIR%\frankenphp.exe" (
    set "FRANKEN=%RUNTIME_DIR%\frankenphp.exe"
) else (
    set "FRANKEN=frankenphp.exe"
)

echo ====================================================
echo   Starting SIMS Web Server (Caddy + PHP 8.2 FastCGI)
echo ====================================================
echo Working Directory: %APP_DIR%
echo Public Root:       %APP_PUBLIC%
echo Web Server:        %FRANKEN%
echo.

where "%FRANKEN%" >nul 2>&1
if %errorLevel% neq 0 (
    if not exist "%FRANKEN%" (
        echo [ERROR] Web server executable not found at:
        echo   %FRANKEN%
        echo Please ensure runtime\frankenphp.exe exists.
        pause
        exit /b 1
    )
)

:: 2. Start bundled PHP FastCGI Engine on port 9000 if not already running
set "PHP_CGI=%RUNTIME_DIR%\php\php-cgi.exe"
set "PHP_INI=%RUNTIME_DIR%\php\php.ini"

if exist "%PHP_CGI%" (
    netstat -ano 2>nul | findstr "127.0.0.1:9000 " >nul 2>&1
    if %errorLevel% neq 0 (
        echo [INFO] Starting bundled PHP 8.2 FastCGI Engine on port 9000...
        start /b "" "%PHP_CGI%" -b 127.0.0.1:9000 -c "%PHP_INI%"
        timeout /t 1 /nobreak >nul
    ) else (
        echo [OK] PHP FastCGI Engine is already listening on port 9000.
    )
)

if exist "%SERVICES_DIR%\trust-cert.bat" call "%SERVICES_DIR%\trust-cert.bat"
echo.

echo [INFO] Starting Web Server with Caddyfile (HTTPS :443 & HTTP :80)...
echo [URL] HTTP  (Direct) : http://localhost
echo [URL] HTTPS (Secure) : https://localhost
echo.

"%FRANKEN%" run --adapter caddyfile --config "%APP_DIR%\Caddyfile"
if %errorLevel% equ 0 goto :RUN_EXIT

echo.
echo [WARNING] Web server stopped (Exit code: %errorLevel%).

:RUN_EXIT
echo.
echo ====================================================
echo Web server process has stopped.
echo ====================================================
pause
