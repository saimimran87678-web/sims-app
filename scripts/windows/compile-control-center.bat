@echo off
setlocal enabledelayedexpansion

:: 1. Resolve Root Directory (2 levels up from scripts\windows\)
for %%I in ("%~dp0..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "SRC_FILE=%ROOT_DIR%\scripts\windows\ControlCenter.cs"
set "OUT_EXE=%ROOT_DIR%\Adminova-Control-Center.exe"
set "ICON_FILE=%ROOT_DIR%\resources\icons\adminova.ico"
if not exist "%ICON_FILE%" set "ICON_FILE=%ROOT_DIR%\scripts\build\windows\app.ico"

title Adminova Control Center Compiler
echo ====================================================
echo    Compiling Adminova Native Windows Control Center
echo ====================================================
echo Source File: %SRC_FILE%
echo Output Exe:  %OUT_EXE%
echo Icon File:   %ICON_FILE%
echo.

if not exist "%SRC_FILE%" (
    echo [ERROR] Source code file not found: %SRC_FILE%
    echo Please ensure the script is run inside the SIMS project directory.
    echo.
    pause
    exit /b 1
)

:: 2. Locate csc.exe in standard .NET Framework locations
set "CSC="
if exist "%WINDIR%\Microsoft.NET\Framework64\v4.0.30319\csc.exe" (
    set "CSC=%WINDIR%\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
) else if exist "%WINDIR%\Microsoft.NET\Framework\v4.0.30319\csc.exe" (
    set "CSC=%WINDIR%\Microsoft.NET\Framework\v4.0.30319\csc.exe"
) else (
    where csc.exe >nul 2>&1
    if !errorLevel! equ 0 set "CSC=csc.exe"
)

if not defined CSC (
    echo [ERROR] Microsoft .NET C# Compiler (csc.exe) not found!
    echo Please ensure .NET Framework 4.5 or higher is enabled on your Windows PC.
    echo (Included by default on Windows 7, 8, 10, and 11 in Windows Features).
    echo.
    pause
    exit /b 1
)

echo [INFO] Using C# Compiler: %CSC%

set "ICON_FLAG="
if exist "%ICON_FILE%" (
    set ICON_FLAG=/win32icon:"%ICON_FILE%"
)

echo [INFO] Compiling executable...
"%CSC%" /target:winexe /optimize+ /platform:anycpu %ICON_FLAG% /out:"%OUT_EXE%" /r:System.dll,System.Windows.Forms.dll,System.Drawing.dll "%SRC_FILE%"
set "COMP_ERR=%errorLevel%"

if %COMP_ERR% equ 0 (
    if exist "%OUT_EXE%" (
        echo.
        echo ====================================================
        echo [OK] Adminova-Control-Center.exe compiled successfully!
        echo Location: %OUT_EXE%
        echo ====================================================
        echo.
        pause
        exit /b 0
    )
)

echo.
echo ====================================================
echo [ERROR] Compilation failed with exit code %COMP_ERR%!
echo Check the compiler output messages above.
echo ====================================================
echo.
pause
exit /b 1
