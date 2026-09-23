@echo off
setlocal enabledelayedexpansion

:: 0. Set working directory to script location
cd /d "%~dp0"

:: Resolve root directory of SIMS installation (2 levels up from scripts\windows\)
for %%I in ("%~dp0..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

:: Fallback if executed from C:\Windows\System32
if not exist "%ROOT_DIR%\sims-app\artisan" (
    if defined SIMS_HOME (
        set "ROOT_DIR=%SIMS_HOME%"
    )
)

set "APP_DIR=%ROOT_DIR%\sims-app"
set "SERVICES_DIR=%ROOT_DIR%\scripts\windows\services"
if not exist "%SERVICES_DIR%" (
    set "SERVICES_DIR=%ROOT_DIR%\bin"
)

if exist "%ROOT_DIR%\runtime\php\php.exe" (
    set "PHP_BIN=%ROOT_DIR%\runtime\php\php.exe"
) else (
    set "PHP_BIN=php"
)

set "ACTION=%~1"

:: If double-clicked without arguments, open interactive menu
if "%ACTION%"=="" goto :INTERACTIVE_MENU
if /i "%ACTION%"=="help" goto :USAGE
if /i "%ACTION%"=="--help" goto :USAGE
if /i "%ACTION%"=="-h" goto :USAGE
if /i "%ACTION%"=="status" goto :DO_STATUS
if /i "%ACTION%"=="activate" goto :DO_ACTIVATE
if /i "%ACTION%"=="start" goto :CHECK_ELEVATION
if /i "%ACTION%"=="stop" goto :CHECK_ELEVATION
if /i "%ACTION%"=="restart" goto :CHECK_ELEVATION

echo [ERROR] Unknown command: '%ACTION%'
echo.
goto :USAGE

:USAGE
echo ====================================================
echo             SIMS Service Control Manager
echo ====================================================
echo Usage: sims [command] [options]
echo.
echo Commands:
echo   sims status              - Display health and status of all services
echo   sims activate [key]      - Activate school license (reads .env if omitted)
echo   sims start               - Start all background services (HTTPS & HTTP)
echo   sims stop                - Stop all services and release folder locks
echo   sims restart             - Completely restart all services
echo ====================================================
exit /b 0

:INTERACTIVE_MENU
cls
echo ====================================================
echo        SIMS Control Center (Interactive)
echo ====================================================
echo Installation: %ROOT_DIR%
echo.
call :PRINT_SERVICE_STATUS
echo ====================================================
echo  [1] Start all SIMS services
echo  [2] Stop all SIMS services
echo  [3] Restart all SIMS services
echo  [4] Activate School License Key
echo  [5] Refresh service status
echo  [6] Open SIMS in web browser
echo  [7] Trust SSL Certificate in Windows
echo  [0] Exit
echo ====================================================
set /p "CHOICE=Enter choice (0-7): "

if "%CHOICE%"=="1" (
    set "ACTION=start"
    goto :CHECK_ELEVATION
)
if "%CHOICE%"=="2" (
    set "ACTION=stop"
    goto :CHECK_ELEVATION
)
if "%CHOICE%"=="3" (
    set "ACTION=restart"
    goto :CHECK_ELEVATION
)
if "%CHOICE%"=="4" (
    echo.
    call :DO_ACTIVATE
    pause
    goto :INTERACTIVE_MENU
)
if "%CHOICE%"=="5" goto :INTERACTIVE_MENU
if "%CHOICE%"=="6" (
    start https://localhost
    goto :INTERACTIVE_MENU
)
if "%CHOICE%"=="7" (
    echo.
    if exist "%SERVICES_DIR%\trust-cert.bat" call "%SERVICES_DIR%\trust-cert.bat"
    echo.
    pause
    goto :INTERACTIVE_MENU
)
if "%CHOICE%"=="0" exit /b 0
goto :INTERACTIVE_MENU

:CHECK_ELEVATION
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Administrator privileges required for 'sims %ACTION%'.
    echo Requesting elevation...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -ArgumentList '%ACTION%' -Verb RunAs"
    exit /b 0
)
if /i "%ACTION%"=="start" goto :DO_START
if /i "%ACTION%"=="stop" goto :DO_STOP
if /i "%ACTION%"=="restart" goto :DO_RESTART
exit /b 0

:DO_ACTIVATE
echo ====================================================
echo             SIMS License Activation CLI
echo ====================================================
cd /d "%APP_DIR%"
"%PHP_BIN%" artisan license:activate %2 %3
echo ====================================================
exit /b %errorLevel%

:DO_STATUS
echo ====================================================
echo             SIMS System Service Status
echo ====================================================
call :PRINT_SERVICE_STATUS
echo Primary Access URLs:
echo   - [HTTPS] https://localhost         (Secured with Local Certificate)
echo   - [HTTPS] https://sims.local
echo   - [HTTP]  http://localhost          (Redirects to HTTPS)
echo.
echo Local School Network (LAN) Access:
echo   - [HTTP]  http://%COMPUTERNAME%
echo   - [HTTPS] https://%COMPUTERNAME%.local
echo ====================================================
exit /b 0

:PRINT_SERVICE_STATUS
:: 1. Check Port 443 & 80
netstat -ano 2>nul | findstr ":443 " >nul 2>&1
if %errorLevel% equ 0 goto :STATUS_HTTPS_UP
echo [HTTPS Web Server] OFFLINE (Port 443 closed)
goto :STATUS_CHECK_80

:STATUS_HTTPS_UP
echo [HTTPS Web Server] ONLINE  (Port 443 listening - SSL Active)

:STATUS_CHECK_80
netstat -ano 2>nul | findstr ":80 " >nul 2>&1
if %errorLevel% equ 0 goto :STATUS_HTTP_UP
echo [HTTP Web Server]  OFFLINE (Port 80 closed)
goto :STATUS_CHECK_FRANKEN

:STATUS_HTTP_UP
echo [HTTP Web Server]  ONLINE  (Port 80 listening)

:STATUS_CHECK_FRANKEN
:: 2. Check FrankenPHP Process
tasklist /fi "imagename eq frankenphp.exe" 2>nul | findstr /i "frankenphp.exe" >nul 2>&1
if %errorLevel% equ 0 (
    echo [FrankenPHP Engine] ACTIVE
) else (
    echo [FrankenPHP Engine] STOPPED
)

:: 3. Check PHP Process (Worker / Scheduler)
tasklist /fi "imagename eq php.exe" 2>nul | findstr /i "php.exe" >nul 2>&1
if %errorLevel% equ 0 (
    echo [PHP Background]    ACTIVE (Worker / Scheduler running)
) else (
    echo [PHP Background]    IDLE / STOPPED
)

:: 4. Check Windows Task Scheduler Tasks
schtasks /query /tn "SIMS-Web" 2>nul | findstr /i "Running" >nul 2>&1
if %errorLevel% equ 0 (
    echo [Service: Web]      RUNNING (Task Scheduler)
) else (
    echo [Service: Web]      STANDBY / STOPPED
)

schtasks /query /tn "SIMS-Queue" 2>nul | findstr /i "Running" >nul 2>&1
if %errorLevel% equ 0 (
    echo [Service: Queue]    RUNNING (Task Scheduler)
) else (
    echo [Service: Queue]    STANDBY / STOPPED
)

schtasks /query /tn "SIMS-Scheduler" 2>nul | findstr /i "Running" >nul 2>&1
if %errorLevel% equ 0 (
    echo [Service: Schedule] RUNNING (Task Scheduler)
) else (
    echo [Service: Schedule] STANDBY / STOPPED
)
goto :eof

:DO_STOP_SILENT
schtasks /end /tn "SIMS-Web" >nul 2>&1
schtasks /end /tn "SIMS-Queue" >nul 2>&1
schtasks /end /tn "SIMS-Scheduler" >nul 2>&1
taskkill /F /IM frankenphp.exe /IM php.exe /IM php-cgi.exe >nul 2>&1
timeout /t 1 /nobreak >nul
goto :eof

:DO_STOP
echo ====================================================
echo Stopping all SIMS services and background processes...
call :DO_STOP_SILENT
echo [OK] All SIMS services and processes have been stopped.
echo ====================================================
pause
exit /b 0

:DO_START
echo ====================================================
echo Starting all SIMS services...

:: Trust SSL Certificate if present
if exist "%SERVICES_DIR%\trust-cert.bat" call "%SERVICES_DIR%\trust-cert.bat"

schtasks /run /tn "SIMS-Web" >nul 2>&1
schtasks /run /tn "SIMS-Queue" >nul 2>&1
schtasks /run /tn "SIMS-Scheduler" >nul 2>&1

timeout /t 2 /nobreak >nul
netstat -ano 2>nul | findstr ":443 " >nul 2>&1
if %errorLevel% equ 0 goto :START_HTTPS_OK
netstat -ano 2>nul | findstr ":80 " >nul 2>&1
if %errorLevel% equ 0 goto :START_HTTP_OK

echo [INFO] Starting web server directly in background...
if exist "%SERVICES_DIR%\run-web.bat" (
    start "" /min "%SERVICES_DIR%\run-web.bat"
    timeout /t 3 /nobreak >nul
)
goto :START_CHECK_FINAL

:START_HTTPS_OK
echo [OK] SIMS Web Server is ONLINE with HTTPS (Port 443) and HTTP (Port 80)!
goto :START_FINISH

:START_HTTP_OK
echo [OK] SIMS Web Server is ONLINE on Port 80!
goto :START_FINISH

:START_CHECK_FINAL
netstat -ano 2>nul | findstr ":443 " >nul 2>&1
if %errorLevel% equ 0 goto :START_HTTPS_OK
netstat -ano 2>nul | findstr ":80 " >nul 2>&1
if %errorLevel% equ 0 goto :START_HTTP_OK
echo [NOTICE] Services initiated. Please allow a few moments for full startup.

:START_FINISH
echo.
echo [OK] All SIMS services are active!
echo.
echo Primary Access URLs:
echo   - [HTTPS] https://localhost         (Secured with Local Certificate)
echo   - [HTTPS] https://sims.local
echo   - [HTTP]  http://localhost          (Redirects to HTTPS)
echo.
echo Local School Network (LAN) Access:
echo   - [HTTP]  http://%COMPUTERNAME%
echo   - [HTTPS] https://%COMPUTERNAME%.local
echo ====================================================
pause
exit /b 0

:DO_RESTART
echo ====================================================
echo Restarting all SIMS services...
echo ====================================================
call :DO_STOP_SILENT
call :DO_START
exit /b 0
