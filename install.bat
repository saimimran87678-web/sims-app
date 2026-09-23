@echo off
setlocal
cd /d "%~dp0"

:: SIMS Quick-Launch Installer (Delegates to scripts\windows\install.bat)
if exist "%~dp0scripts\windows\install.bat" (
    call "%~dp0scripts\windows\install.bat" %*
    exit /b %errorLevel%
)

echo [ERROR] Installer engine not found at scripts\windows\install.bat!
echo Please ensure the release package was extracted completely.
echo.
pause
exit /b 1
