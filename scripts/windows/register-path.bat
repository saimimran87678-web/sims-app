@echo off
setlocal enabledelayedexpansion
title SIMS Global CLI Registration

:: Resolve installation root directory (supports running from root or scripts\windows)
if exist "%~dp0sims-app\artisan" (
    set "ROOT_DIR=%~dp0"
) else if exist "%~dp0..\..\sims-app\artisan" (
    for %%I in ("%~dp0..\..") do set "ROOT_DIR=%%~fI"
) else (
    set "ROOT_DIR=%~dp0"
)
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

echo ====================================================
echo        SIMS Global Command-Line Registration
echo ====================================================
echo Installation Directory: %ROOT_DIR%
echo.

if not exist "%ROOT_DIR%\sims-app\artisan" (
    echo [ERROR] Could not verify SIMS directory at: %ROOT_DIR%
    echo Please run this script directly from your SIMS installation folder.
    if /i not "%~1"=="--silent" pause
    exit /b 1
)

:: 1. Check Administrator Privileges
net session >nul 2>&1
set "IS_ADMIN=0"
if %errorLevel% equ 0 set "IS_ADMIN=1"

:: 2. Set SIMS_HOME in Current User Environment
echo [1/4] Configuring SIMS_HOME environment variable...
reg add "HKCU\Environment" /v SIMS_HOME /t REG_SZ /d "%ROOT_DIR%" /f >nul 2>&1
setx SIMS_HOME "%ROOT_DIR%" >nul 2>&1

:: If Admin, set SIMS_HOME and InstallPath system-wide
if "%IS_ADMIN%"=="1" (
    reg add "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v SIMS_HOME /t REG_SZ /d "%ROOT_DIR%" /f >nul 2>&1
    reg add "HKLM\SOFTWARE\Adminova\SIMS" /v InstallPath /t REG_SZ /d "%ROOT_DIR%" /f >nul 2>&1
    setx SIMS_HOME "%ROOT_DIR%" /m >nul 2>&1
    echo [OK] SIMS_HOME configured for System and Current User.
) else (
    echo [OK] SIMS_HOME configured for Current User.
)

:: 3. Add ROOT_DIR to Windows PATH (User PATH + System PATH if elevated)
echo [2/4] Registering installation folder in Windows PATH...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$root = '%ROOT_DIR%';" ^
    "$userPath = [Environment]::GetEnvironmentVariable('Path', 'User');" ^
    "if ($userPath -notlike ('*' + $root + '*')) {" ^
    "    $newPath = if ([string]::IsNullOrWhiteSpace($userPath)) { $root } else { $userPath.TrimEnd(';') + ';' + $root };" ^
    "    [Environment]::SetEnvironmentVariable('Path', $newPath, 'User');" ^
    "}"

if "%IS_ADMIN%"=="1" (
    powershell -NoProfile -ExecutionPolicy Bypass -Command ^
        "$root = '%ROOT_DIR%';" ^
        "$sysPath = [Environment]::GetEnvironmentVariable('Path', 'Machine');" ^
        "if ($sysPath -notlike ('*' + $root + '*')) {" ^
        "    $newPath = if ([string]::IsNullOrWhiteSpace($sysPath)) { $root } else { $sysPath.TrimEnd(';') + ';' + $root };" ^
        "    [Environment]::SetEnvironmentVariable('Path', $newPath, 'Machine');" ^
        "}"
    echo [OK] Added to System PATH and User PATH.
) else (
    echo [OK] Added to User PATH.
)

:: 4. Deploy universal shim to Windows system/app execution paths
echo [3/4] Installing universal command shims...
set "SHIM_SRC=%ROOT_DIR%\sims.bat"

:: User-level WindowsApps folder (included in Windows 10/11 default User PATH, no admin required)
if defined LOCALAPPDATA (
    if not exist "%LOCALAPPDATA%\Microsoft\WindowsApps" mkdir "%LOCALAPPDATA%\Microsoft\WindowsApps" >nul 2>&1
    if exist "%LOCALAPPDATA%\Microsoft\WindowsApps" (
        copy /y "%SHIM_SRC%" "%LOCALAPPDATA%\Microsoft\WindowsApps\sims.bat" >nul 2>&1
        if !errorLevel! equ 0 echo [OK] Installed user shim: %%LOCALAPPDATA%%\Microsoft\WindowsApps\sims.bat
    )
)

:: System32 folder (if elevated) - Overwrites any stale/broken shim
if "%IS_ADMIN%"=="1" (
    if exist "%WINDIR%\System32" (
        copy /y "%SHIM_SRC%" "%WINDIR%\System32\sims.bat" >nul 2>&1
        if !errorLevel! equ 0 echo [OK] Updated System32 shim: %WINDIR%\System32\sims.bat
    )
)

:: 5. Refresh Environment Variables in Current Session
echo [4/4] Refreshing active environment variables...
set "SIMS_HOME=%ROOT_DIR%"
set "PATH=%ROOT_DIR%;%PATH%"

:: Broadcast setting change message to all top-level windows
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$HWND_BROADCAST = [IntPtr]0xffff;" ^
    "$WM_SETTINGCHANGE = 0x1a;" ^
    "$result = [IntPtr]::Zero;" ^
    "Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class Win32 { [DllImport(\"user32.dll\", SetLastError = true, CharSet = CharSet.Auto)] public static extern IntPtr SendMessageTimeout(IntPtr hWnd, uint Msg, UIntPtr wParam, string lParam, uint fuFlags, uint uTimeout, out IntPtr lpdwResult); }';" ^
    "[Win32]::SendMessageTimeout($HWND_BROADCAST, $WM_SETTINGCHANGE, [UIntPtr]::Zero, 'Environment', 2, 2000, [ref]$result) | Out-Null" >nul 2>&1

echo.
echo ====================================================
echo  🎉 SIMS Command-Line Interface is Now Active Globally!
echo ====================================================
echo You can now open a new Command Prompt or PowerShell
echo from ANY folder or drive and type:
echo.
echo    sims status     - View live services and LAN IP
echo    sims start      - Start services with auto-reclamation
echo    sims stop       - Stop all services cleanly
echo    sims restart    - Restart web server and workers
echo    sims update     - Check and apply updates safely
echo    sims activate   - Activate school license key
echo ====================================================
echo.
if not "%IS_ADMIN%"=="1" (
    echo [TIP] For complete system-wide registration (including System32),
    echo       right-click register-path.bat and select 'Run as administrator'.
    echo.
)
if /i not "%~1"=="--silent" pause
exit /b 0
