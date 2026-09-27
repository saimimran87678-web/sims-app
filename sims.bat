@echo off
setlocal

:: ==============================================================================
:: SIMS Universal Command-Line Dispatcher & Path Resolver
:: This file can be run directly from the SIMS root directory, or dispatched from
:: C:\Windows\System32, %LOCALAPPDATA%\Microsoft\WindowsApps, or any directory on PATH.
:: ==============================================================================

:: 1. Check relative path if executed directly from application root
if exist "%~dp0scripts\windows\sims.bat" (
    call "%~dp0scripts\windows\sims.bat" %*
    exit /b %errorLevel%
)

:: 2. Check active SIMS_HOME environment variable
if defined SIMS_HOME (
    if exist "%SIMS_HOME%\scripts\windows\sims.bat" (
        call "%SIMS_HOME%\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)

:: 3. Read SIMS_HOME directly from Registry (HKLM & HKCU)
:: Works even in command prompt windows opened before environment variables were refreshed
for /f "tokens=2*" %%A in ('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v SIMS_HOME 2^>nul') do (
    if exist "%%~B\scripts\windows\sims.bat" (
        call "%%~B\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)
for /f "tokens=2*" %%A in ('reg query "HKCU\Environment" /v SIMS_HOME 2^>nul') do (
    if exist "%%~B\scripts\windows\sims.bat" (
        call "%%~B\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)
for /f "tokens=2*" %%A in ('reg query "HKLM\SOFTWARE\Adminova\SIMS" /v InstallPath 2^>nul') do (
    if exist "%%~B\scripts\windows\sims.bat" (
        call "%%~B\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)
for /f "tokens=2*" %%A in ('reg query "HKCU\SOFTWARE\Adminova\SIMS" /v InstallPath 2^>nul') do (
    if exist "%%~B\scripts\windows\sims.bat" (
        call "%%~B\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)
for /f "tokens=2*" %%A in ('reg query "HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\{D37F28A1-4B9E-4811-9F22-A8E2104BC99E}_is1" /v InstallLocation 2^>nul') do (
    if exist "%%~B\scripts\windows\sims.bat" (
        call "%%~B\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)

:: 4. Check current working directory
if exist "%CD%\scripts\windows\sims.bat" (
    call "%CD%\scripts\windows\sims.bat" %*
    exit /b %errorLevel%
)

:: 5. Check well-known Program Files and LocalAppData paths
if exist "%ProgramFiles%\SIMS\scripts\windows\sims.bat" (
    call "%ProgramFiles%\SIMS\scripts\windows\sims.bat" %*
    exit /b %errorLevel%
)
if exist "%ProgramFiles(x86)%\SIMS\scripts\windows\sims.bat" (
    call "%ProgramFiles(x86)%\SIMS\scripts\windows\sims.bat" %*
    exit /b %errorLevel%
)
if defined LOCALAPPDATA (
    if exist "%LOCALAPPDATA%\Programs\SIMS\scripts\windows\sims.bat" (
        call "%LOCALAPPDATA%\Programs\SIMS\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)

:: 6. Auto-discover common installation drive letters (H, D, E, F, G, C)
for %%D in (H D E F G C) do (
    if exist "%%D:\SIMS\scripts\windows\sims.bat" (
        call "%%D:\SIMS\scripts\windows\sims.bat" %*
        exit /b %errorLevel%
    )
)

:: 7. If all searches fail, show clear instructions on how to link
echo ====================================================
echo [ERROR] SIMS installation directory could not be located!
echo ====================================================
echo Searched locations:
echo   - Relative path: %~dp0scripts\windows\sims.bat
echo   - SIMS_HOME environment variable and Windows Registry
echo   - Standard paths: H:\SIMS, D:\SIMS, C:\SIMS, Program Files
echo.
echo To register SIMS globally so it works from any directory:
echo   1. Open your SIMS installation folder (e.g. H:\SIMS)
echo   2. Run: register-path.bat (or right-click and 'Run as administrator')
echo ====================================================
echo.
pause
exit /b 1
