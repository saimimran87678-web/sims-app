@echo off
setlocal
cd /d "%~dp0"
if exist "%~dp0Adminova-Control-Center.exe" (
    start "" "%~dp0Adminova-Control-Center.exe"
    exit /b 0
)
if exist "%~dp0control-center.vbs" (
    start "" wscript.exe "%~dp0control-center.vbs"
    exit /b 0
)
start "" powershell.exe -WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\windows\control-panel.ps1"
exit /b 0
