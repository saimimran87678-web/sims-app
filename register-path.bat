@echo off
setlocal
cd /d "%~dp0"

:: SIMS Quick Register (Delegates to scripts\windows\register-path.bat)
if exist "%~dp0scripts\windows\register-path.bat" (
    call "%~dp0scripts\windows\register-path.bat" %*
    exit /b %errorLevel%
)

echo [ERROR] Registration engine not found at scripts\windows\register-path.bat!
echo Please ensure the SIMS release package was extracted completely.
echo.
if /i not "%~1"=="--silent" pause
exit /b 1
