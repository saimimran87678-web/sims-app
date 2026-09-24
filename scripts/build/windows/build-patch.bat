@echo off
setlocal
cd /d "%~dp0"
title SIMS Delta Patch Packaging Engine

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-patch.ps1" %*
if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Patch packaging failed with exit code %errorLevel%!
    pause
    exit /b %errorLevel%
)

echo.
pause
