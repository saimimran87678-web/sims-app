@echo off
setlocal
cd /d "%~dp0"

:: 1. Launch Adminova Control Center if already compiled
if exist "%~dp0Adminova-Control-Center.exe" (
    start "" "%~dp0Adminova-Control-Center.exe"
    exit /b 0
)

:: 2. Auto-compile native Control Center if missing
if exist "%~dp0scripts\windows\compile-control-center.bat" (
    echo [INFO] Compiling Adminova Control Center...
    call "%~dp0scripts\windows\compile-control-center.bat"
    if exist "%~dp0Adminova-Control-Center.exe" (
        start "" "%~dp0Adminova-Control-Center.exe"
        exit /b 0
    )
)

echo.
echo [ERROR] Adminova-Control-Center.exe not found and could not be compiled.
echo Please run scripts\windows\compile-control-center.bat manually.
echo.
pause
exit /b 1
