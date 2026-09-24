@echo off
setlocal
cd /d "%~dp0"
title SIMS Windows Installer Compiler

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-installer.ps1" %*
if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Installer compilation failed with exit code %errorLevel%!
    pause
    exit /b %errorLevel%
)

echo.
pause
