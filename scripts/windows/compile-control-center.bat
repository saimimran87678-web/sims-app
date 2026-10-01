@echo off
setlocal
cd /d "%~dp0"
title Adminova Control Center Compiler

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0..\build\windows\compile-control-center.ps1" %*
if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Control Center compilation failed with exit code %errorLevel%!
    pause
    exit /b %errorLevel%
)

echo.
pause