@echo off
setlocal enabledelayedexpansion

:: 1. Resolve Root Directory (2 levels up from scripts\windows\)
for %%I in ("%~dp0..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "SRC_FILE=%ROOT_DIR%\scripts\windows\ControlCenter.cs"
set "OUT_EXE=%ROOT_DIR%\Adminova-Control-Center.exe"
set "ICON_FILE=%ROOT_DIR%\resources\icons\adminova.ico"
if not exist "%ICON_FILE%" set "ICON_FILE=%ROOT_DIR%\scripts\build\windows\app.ico"

echo ====================================================
echo    Compiling Adminova Native Windows Control Center
echo ====================================================
echo Source File: %SRC_FILE%
echo Output Exe:  %OUT_EXE%
echo Icon File:   %ICON_FILE%
echo.

if not exist "%SRC_FILE%" (
    echo [ERROR] Source code file not found: %SRC_FILE%
    exit /b 1
)

:: 2. Locate csc.exe
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
    echo Please ensure .NET Framework 4.5+ is enabled on Windows.
    exit /b 1
)

echo [INFO] Using C# Compiler: %CSC%

set "ICON_FLAG="
if exist "%ICON_FILE%" (
    set "ICON_FLAG=/win32icon:"%ICON_FILE%""
)

"%CSC%" /target:winexe /optimize+ /platform:anycpu %ICON_FLAG% /out:"%OUT_EXE%" /r:System.dll,System.Windows.Forms.dll,System.Drawing.dll "%SRC_FILE%"
if %errorLevel% equ 0 (
    echo.
    echo ====================================================
    echo [OK] Adminova-Control-Center.exe compiled successfully!
    echo Location: %OUT_EXE%
    echo ====================================================
    exit /b 0
) else (
    echo.
    echo [ERROR] Compilation failed with exit code %errorLevel%!
    exit /b 1
)
