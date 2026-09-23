@echo off
setlocal enabledelayedexpansion

set "BIN_DIR=%~dp0"
if "%BIN_DIR:~-1%"=="\" set "BIN_DIR=%BIN_DIR:~0,-1%"
for %%I in ("%BIN_DIR%") do set "ROOT_DIR=%%~dpI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "APP_DIR=%ROOT_DIR%\sims-app"
set "RUNTIME_DIR=%ROOT_DIR%\runtime"

cd /d "%APP_DIR%"

if exist "%RUNTIME_DIR%\php\php.exe" (
    set "PHP_BIN=%RUNTIME_DIR%\php\php.exe"
) else (
    set "PHP_BIN=php"
)

echo Starting SIMS Background Queue Worker...
"%PHP_BIN%" artisan queue:work --sleep=3 --tries=3
if %errorLevel% neq 0 (
    echo [ERROR] Queue worker stopped with code %errorLevel%.
    pause
)
