@echo off
setlocal enabledelayedexpansion

:: 1. Resolve Root Directory cleanly without trailing slashes
set "BIN_DIR=%~dp0"
if "%BIN_DIR:~-1%"=="\" set "BIN_DIR=%BIN_DIR:~0,-1%"
for %%I in ("%BIN_DIR%") do set "ROOT_DIR=%%~dpI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "APP_DIR=%ROOT_DIR%\sims-app"
set "RUNTIME_DIR=%ROOT_DIR%\runtime"

cd /d "%APP_DIR%"

if exist "%RUNTIME_DIR%\frankenphp.exe" (
    set "FRANKEN=%RUNTIME_DIR%\frankenphp.exe"
) else (
    set "FRANKEN=frankenphp.exe"
)

echo ====================================================
echo   Starting SIMS Web Server (FrankenPHP)
echo ====================================================
echo Working Directory: %APP_DIR%
echo Public Root:       %APP_DIR%\public
echo Executable:        %FRANKEN%
echo.

where "%FRANKEN%" >nul 2>&1
if %errorLevel% neq 0 (
    if not exist "%FRANKEN%" (
        echo [ERROR] FrankenPHP executable not found at:
        echo   %FRANKEN%
        echo Please ensure runtime\frankenphp.exe exists.
        pause
        exit /b 1
    )
)

if exist "%APP_DIR%\Caddyfile" goto :RUN_CADDY
goto :RUN_STANDARD

:RUN_CADDY
echo [INFO] Starting FrankenPHP with Caddyfile (HTTPS :443 & HTTP :80)...
"%FRANKEN%" run --config "%APP_DIR%\Caddyfile"
if %errorLevel% equ 0 goto :RUN_EXIT

echo.
echo [WARNING] FrankenPHP Caddyfile mode stopped or failed (Exit code: %errorLevel%).
echo [INFO] Attempting fallback to standard Port 80 HTTP mode...

:RUN_STANDARD
echo [INFO] Starting FrankenPHP in standard Port 80 HTTP mode...
"%FRANKEN%" php-server --listen 0.0.0.0:80 --root "%APP_DIR%\public"

:RUN_EXIT
echo.
echo ====================================================
echo Web server process has stopped.
echo ====================================================
pause
