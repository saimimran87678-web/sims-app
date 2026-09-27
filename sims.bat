@echo off
setlocal

:: 1. Check relative path if executed directly from application root
if exist "%~dp0scripts\windows\sims.bat" (
    call "%~dp0scripts\windows\sims.bat" %*
    exit /b %errorLevel%
)

:: 2. Check SIMS_HOME environment variable (set during install)
if defined SIMS_HOME (
    if exist "%SIMS_HOME%\scripts\windows\sims.bat" (
        call "%SIMS_HOME%\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)

:: 3. Check current working directory
if exist "%CD%\scripts\windows\sims.bat" (
    call "%CD%\scripts\windows\sims.bat" %*
    exit /b %errorLevel%
)

:: 4. Auto-discover common installation drives (H, D, E, C, etc.)
for %%D in (H D E F G C) do (
    if exist "%%D:\SIMS\scripts\windows\sims.bat" (
        call "%%D:\SIMS\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)

echo [ERROR] SIMS installation directory could not be located!
echo Please navigate to your SIMS folder (e.g. H:\SIMS) and run sims from there.
echo.
pause
exit /b 1

