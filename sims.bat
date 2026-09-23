@echo off
setlocal
cd /d "%~dp0"

:: SIMS Quick-Launch Service Controller (Delegates to scripts\windows\sims.bat)
if exist "%~dp0scripts\windows\sims.bat" (
    call "%~dp0scripts\windows\sims.bat" %*
    exit /b %errorLevel%
)

echo [ERROR] SIMS Service Manager not found at scripts\windows\sims.bat!
echo.
pause
exit /b 1
