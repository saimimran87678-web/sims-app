@echo off
setlocal enabledelayedexpansion
set "BIN_DIR=%~dp0"
set "ROOT_DIR=%BIN_DIR%.."
set "APP_DIR=%ROOT_DIR%\sims-app"
set "RUNTIME_DIR=%ROOT_DIR%\runtime"

cd /d "%APP_DIR%"

if exist "%RUNTIME_DIR%\frankenphp.exe" (
    set "FRANKEN=%RUNTIME_DIR%\frankenphp.exe"
) else (
    set "FRANKEN=frankenphp.exe"
)

echo Starting SIMS Web Server (FrankenPHP) on Port 80...
"%FRANKEN%" php-server --listen :80 --root "%APP_DIR%\public"
