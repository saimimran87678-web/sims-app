@echo off
setlocal enabledelayedexpansion

:: 1. Resolve Root Directory cleanly (3 levels up from scripts\windows\services\)
for %%I in ("%~dp0..\..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "APP_DIR=%ROOT_DIR%\sims-app"
set "RUNTIME_DIR=%ROOT_DIR%\runtime"

set "PATH=%RUNTIME_DIR%\php;%RUNTIME_DIR%;%PATH%"

cd /d "%APP_DIR%"

if exist "%RUNTIME_DIR%\php\php.exe" (
    set "PHP_BIN=%RUNTIME_DIR%\php\php.exe"
) else (
    set "PHP_BIN=php"
)

echo Starting SIMS Task Scheduler...
"%PHP_BIN%" artisan schedule:work
if %errorLevel% neq 0 (
    echo [ERROR] Scheduler stopped with code %errorLevel%.
    pause
)
