@echo off
setlocal
cd /d "%~dp0"
title SIMS Release Packaging Pipeline

echo ====================================================
echo      SIMS Release Packaging Pipeline (Windows)
echo ====================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-release.ps1" %*

if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Build packaging failed with error code %errorLevel%.
    pause
    exit /b %errorLevel%
)

echo.
pause
exit /b 0
