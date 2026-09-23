@echo off
setlocal
cd /d "%~dp0"
title SIMS Portable Runtime Downloader

echo ====================================================
echo    SIMS Windows Portable Runtime Downloader
echo ====================================================
echo This tool downloads FrankenPHP and Portable PHP 8.2
echo into the .\runtime\ folder for zero-install deployment.
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0download-runtime.ps1"

if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Runtime download failed with error code %errorLevel%.
    pause
    exit /b %errorLevel%
)

echo.
pause
exit /b 0
