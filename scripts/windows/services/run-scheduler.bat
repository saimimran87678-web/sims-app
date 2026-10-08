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

echo Starting SIMS Task Scheduler (Self-Healing Watchdog)...
:SCHEDULER_LOOP
"%PHP_BIN%" artisan schedule:work
ping 127.0.0.1 -n 5 >nul
goto :SCHEDULER_LOOP
