@echo off
setlocal enabledelayedexpansion

for %%I in ("%~dp0..") do set "ROOT_DIR=%%~fI"
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
