@echo off
setlocal enabledelayedexpansion

:: 0. Set working directory to script location
cd /d "%~dp0"

:: Resolve root directory of SIMS installation (2 levels up from scripts\windows\)
for %%I in ("%~dp0..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

:: Fallback if executed from C:\Windows\System32 or outside root
if not exist "%ROOT_DIR%\sims-app\artisan" (
    if defined SIMS_HOME (
        set "ROOT_DIR=%SIMS_HOME%"
    )
)
if not exist "%ROOT_DIR%\sims-app\artisan" (
    for /f "tokens=2*" %%A in ('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v SIMS_HOME 2^>nul') do set "ROOT_DIR=%%~B"
)
if not exist "%ROOT_DIR%\sims-app\artisan" (
    for /f "tokens=2*" %%A in ('reg query "HKCU\Environment" /v SIMS_HOME 2^>nul') do set "ROOT_DIR=%%~B"
)
if not exist "%ROOT_DIR%\sims-app\artisan" (
    for /f "tokens=2*" %%A in ('reg query "HKLM\SOFTWARE\Adminova\SIMS" /v InstallPath 2^>nul') do set "ROOT_DIR=%%~B"
)
if not exist "%ROOT_DIR%\sims-app\artisan" (
    for %%D in (H D E F G C) do (
        if exist "%%D:\SIMS\sims-app\artisan" set "ROOT_DIR=%%D:\SIMS"
    )
)

set "APP_DIR=%ROOT_DIR%\sims-app"
set "SERVICES_DIR=%ROOT_DIR%\scripts\windows\services"
if not exist "%SERVICES_DIR%" (
    set "SERVICES_DIR=%ROOT_DIR%\bin"
)

if exist "%ROOT_DIR%\runtime\php\php.exe" (
    set "PHP_BIN=%ROOT_DIR%\runtime\php\php.exe"
    set "PATH=%ROOT_DIR%\runtime\php;%ROOT_DIR%\runtime;%PATH%"
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
if /i "%ACTION%"=="update" goto :DO_UPDATE
if /i "%ACTION%"=="compile" goto :DO_COMPILE
if /i "%ACTION%"=="register" goto :DO_REGISTER
if /i "%ACTION%"=="setup-cli" goto :DO_REGISTER
if /i "%ACTION%"=="path" goto :DO_REGISTER

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
echo   sims start               - Start all background services (HTTPS and HTTP)
echo   sims stop                - Stop all services and release folder locks
echo   sims restart             - Completely restart all services
echo   sims update              - Check and apply automated delta updates safely
echo   sims compile             - Recompile Adminova Control Center executable
echo   sims register            - Register 'sims' globally in Windows PATH
echo ====================================================
exit /b 0

:INTERACTIVE_MENU
cls
set "INTERACTIVE=1"
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
echo  [7] Register 'sims' globally in Windows PATH
echo  [8] Trust SSL Certificate in Windows
echo  [9] Check and Apply System Updates
echo  [10] Recompile Control Center Native App
echo  [0] Exit
echo ====================================================
set /p "CHOICE=Enter choice (0-10): "

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
    call :DO_REGISTER
    goto :INTERACTIVE_MENU
)
if "%CHOICE%"=="8" (
    echo.
    if exist "%SERVICES_DIR%\trust-cert.bat" call "%SERVICES_DIR%\trust-cert.bat"
    echo.
    pause
    goto :INTERACTIVE_MENU
)
if "%CHOICE%"=="9" (
    echo.
    call :DO_UPDATE
    pause
    goto :INTERACTIVE_MENU
)
if "%CHOICE%"=="10" (
    echo.
    call :DO_COMPILE
    goto :INTERACTIVE_MENU
)
if "%CHOICE%"=="0" exit /b 0
goto :INTERACTIVE_MENU

:CHECK_ELEVATION
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Administrator privileges required for 'sims %ACTION%'.
    echo Requesting elevation...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -ArgumentList '%*' -Verb RunAs -Wait"
    exit /b 0
)
if /i "%ACTION%"=="start" goto :DO_START
if /i "%ACTION%"=="stop" goto :DO_STOP
if /i "%ACTION%"=="restart" goto :DO_RESTART
exit /b 0

:DO_COMPILE
echo ====================================================
echo     Recompiling Adminova Native Control Center
echo ====================================================
if exist "%ROOT_DIR%\scripts\windows\compile-control-center.bat" (
    call "%ROOT_DIR%\scripts\windows\compile-control-center.bat"
) else (
    echo [ERROR] compile-control-center.bat not found!
    pause
)
if defined INTERACTIVE (
    pause
    goto :INTERACTIVE_MENU
)
exit /b %errorLevel%

:DO_UPDATE
echo ====================================================
echo             SIMS Safe System Update Manager
echo ====================================================
cd /d "%APP_DIR%"
"%PHP_BIN%" artisan sims:update %2 %3 %4 %5
set "UPDATE_EXIT=%errorLevel%"

if %UPDATE_EXIT% equ 0 (
    if exist "%ROOT_DIR%\scripts\windows\compile-control-center.bat" (
        echo [INFO] Recompiling Control Center with latest updates...
        call "%ROOT_DIR%\scripts\windows\compile-control-center.bat" >nul 2>&1
    )
)
echo ====================================================
exit /b %UPDATE_EXIT%

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
call :DETECT_LAN_IP

echo Primary Access URLs (This Computer):
echo   - [HTTPS] https://localhost         (Secured with Local Certificate)
echo   - [HTTP]  http://localhost          (Redirects to HTTPS)
echo.
echo Local School Network (LAN) Access (Other Devices / Wi-Fi):
echo   - [Recommended]  http://!LAN_IP!       (Direct HTTP - no cert warnings on phones)
echo   - [Secure HTTPS] https://!LAN_IP!      (Encrypted HTTPS)
echo   - [Device Name]  http://%COMPUTERNAME%
echo   - [mDNS Domain]  http://%COMPUTERNAME%.local
echo ====================================================
exit /b 0

:DO_REGISTER
echo ====================================================
echo        Registering SIMS CLI in Windows PATH
echo ====================================================
if exist "%ROOT_DIR%\register-path.bat" (
    call "%ROOT_DIR%\register-path.bat" %2 %3
) else if exist "%ROOT_DIR%\scripts\windows\register-path.bat" (
    call "%ROOT_DIR%\scripts\windows\register-path.bat" %2 %3
) else if exist "%~dp0register-path.bat" (
    call "%~dp0register-path.bat" %2 %3
) else (
    echo [ERROR] register-path.bat not found in %ROOT_DIR%!
)
echo ====================================================
if defined INTERACTIVE (
    pause
    goto :INTERACTIVE_MENU
)
exit /b %errorLevel%

:PRINT_SERVICE_STATUS
:: 1. Live Application Health Probe (Queries Laravel /ping-internal directly)
set "APP_PROBE="
for /f "usebackq delims=" %%H in (`powershell -NoProfile -Command "[System.Net.ServicePointManager]::ServerCertificateValidationCallback = {$true}; try { $r = Invoke-RestMethod -Uri 'https://localhost/ping-internal' -TimeoutSec 2; Write-Host ('ONLINE (v' + $r.version + ' | DB: ' + $r.database + ' | OPcache: ' + $r.opcache.hit_rate + ' | ' + $r.opcache.cached_scripts + ' scripts)') } catch { try { $r2 = Invoke-RestMethod -Uri 'http://localhost/ping-internal' -TimeoutSec 2; Write-Host ('ONLINE (v' + $r2.version + ' | DB: ' + $r2.database + ')') } catch { Write-Host 'OFFLINE (Web server not responding)' } }" 2^>nul`) do (
    set "APP_PROBE=%%H"
)
if defined APP_PROBE (
    echo [App Health Probe] !APP_PROBE!
)

:: 2. Check Port 443 (HTTPS) - Verify LISTENING state and process ownership
set "PORT_443_PID="
set "PORT_443_PROC="
for /f "tokens=5" %%B in ('netstat -ano -p tcp 2^>nul ^| findstr /R /C:":443 .*LISTENING"') do (
    set "PORT_443_PID=%%B"
)
if defined PORT_443_PID (
    for /f "tokens=1" %%P in ('tasklist /fi "PID eq !PORT_443_PID!" /fo csv /nh 2^>nul') do set "PORT_443_PROC=%%~P"
    if /i "!PORT_443_PROC!"=="frankenphp.exe" (
        echo [HTTPS Port 443]   ONLINE  (frankenphp.exe listening, PID: !PORT_443_PID! - SSL Active)
    ) else if not "!PORT_443_PROC!"=="" (
        echo [HTTPS Port 443]   CONFLICT (!PORT_443_PROC! listening, PID: !PORT_443_PID! - NOT SIMS)
    ) else (
        echo [HTTPS Port 443]   LISTENING (Port 443 active, PID: !PORT_443_PID!)
    )
) else (
    echo [HTTPS Port 443]   OFFLINE (Port 443 closed)
)

:: 3. Check Port 80 (HTTP) - Verify LISTENING state and process ownership
set "PORT_80_PID="
set "PORT_80_PROC="
for /f "tokens=5" %%B in ('netstat -ano -p tcp 2^>nul ^| findstr /R /C:":80 .*LISTENING"') do (
    set "PORT_80_PID=%%B"
)
if defined PORT_80_PID (
    for /f "tokens=1" %%P in ('tasklist /fi "PID eq !PORT_80_PID!" /fo csv /nh 2^>nul') do set "PORT_80_PROC=%%~P"
    if /i "!PORT_80_PROC!"=="frankenphp.exe" (
        echo [HTTP Port 80]     ONLINE  (frankenphp.exe listening, PID: !PORT_80_PID!)
    ) else if not "!PORT_80_PROC!"=="" (
        echo [HTTP Port 80]     CONFLICT (!PORT_80_PROC! listening, PID: !PORT_80_PID! - NOT SIMS)
    ) else (
        echo [HTTP Port 80]     LISTENING (Port 80 active, PID: !PORT_80_PID!)
    )
) else (
    echo [HTTP Port 80]     OFFLINE (Port 80 closed)
)

:: 4. Check Port 8000 (Fallback Web Server)
set "PORT_8000_PID="
for /f "tokens=5" %%B in ('netstat -ano -p tcp 2^>nul ^| findstr /R /C:":8000 .*LISTENING"') do (
    set "PORT_8000_PID=%%B"
)
if defined PORT_8000_PID (
    echo [Fallback Port 8000] ONLINE (Listening, PID: !PORT_8000_PID!)
)

:: 5. Check PHP FastCGI Worker Pool (Ports 9000 & 9001)
set "FCGI_9000="
set "FCGI_9001="
for /f "tokens=5" %%B in ('netstat -ano -p tcp 2^>nul ^| findstr /R /C:":9000 .*LISTENING"') do set "FCGI_9000=%%B"
for /f "tokens=5" %%B in ('netstat -ano -p tcp 2^>nul ^| findstr /R /C:":9001 .*LISTENING"') do set "FCGI_9001=%%B"
if defined FCGI_9000 (
    echo [PHP FastCGI:9000] ONLINE  (Worker 1 ready, PID: !FCGI_9000!)
) else (
    echo [PHP FastCGI:9000] OFFLINE (Port 9000 closed)
)
if defined FCGI_9001 (
    echo [PHP FastCGI:9001] ONLINE  (Worker 2 ready, PID: !FCGI_9001!)
) else (
    echo [PHP FastCGI:9001] OFFLINE (Port 9001 closed)
)

:: 6. Check Active Process Counts
set "CGI_COUNT=0"
for /f %%C in ('tasklist /fi "imagename eq php-cgi.exe" /nh 2^>nul ^| find /c /i "php-cgi.exe"') do set "CGI_COUNT=%%C"
if !CGI_COUNT! gtr 0 (
    echo [FastCGI Pool]     ACTIVE  (!CGI_COUNT! php-cgi.exe worker processes)
) else (
    echo [FastCGI Pool]     STOPPED (0 php-cgi.exe worker processes)
)

set "PHP_CLI_COUNT=0"
for /f %%C in ('tasklist /fi "imagename eq php.exe" /nh 2^>nul ^| find /c /i "php.exe"') do set "PHP_CLI_COUNT=%%C"
if !PHP_CLI_COUNT! gtr 0 (
    echo [PHP Background]   ACTIVE  (!PHP_CLI_COUNT! CLI worker/scheduler process running)
) else (
    echo [PHP Background]   IDLE    (No background CLI processes running)
)

:: 7. Check Windows Task Scheduler Tasks
schtasks /query /tn "SIMS-Web" 2>nul | findstr /i "Running" >nul 2>&1
if !errorLevel! equ 0 (
    echo [Service: Web]      RUNNING (Task Scheduler)
) else (
    echo [Service: Web]      STANDBY / STOPPED
)

schtasks /query /tn "SIMS-Queue" 2>nul | findstr /i "Running" >nul 2>&1
if !errorLevel! equ 0 (
    echo [Service: Queue]    RUNNING (Task Scheduler)
) else (
    echo [Service: Queue]    STANDBY / STOPPED
)

schtasks /query /tn "SIMS-Scheduler" 2>nul | findstr /i "Running" >nul 2>&1
if !errorLevel! equ 0 (
    echo [Service: Schedule] RUNNING (Task Scheduler)
) else (
    echo [Service: Schedule] STANDBY / STOPPED
)
goto :eof

:DO_STOP_SILENT
:: 1. End scheduled tasks quietly
schtasks /end /tn "SIMS-Web" >nul 2>&1
schtasks /end /tn "SIMS-Queue" >nul 2>&1
schtasks /end /tn "SIMS-Scheduler" >nul 2>&1

:: 2. Terminate full process trees for all web and PHP engines
taskkill /F /T /IM frankenphp.exe >nul 2>&1
taskkill /F /T /IM php-cgi.exe >nul 2>&1
taskkill /F /T /IM php.exe >nul 2>&1
taskkill /F /T /IM Adminova-Control-Center.exe >nul 2>&1

:: 3. Forcefully free any lingering processes holding SIMS network ports
for %%P in (443 80 8000 9000 9001) do (
    for /f "tokens=5" %%a in ('netstat -aon 2^>nul ^| findstr ":%%P "') do (
        if not "%%a"=="0" taskkill /F /PID %%a >nul 2>&1
    )
)
ping 127.0.0.1 -n 2 >nul
goto :eof

:DO_STOP
echo ====================================================
echo Stopping all SIMS services and background processes...
call :DO_STOP_SILENT
echo [OK] All SIMS services terminated and network ports released.
echo ====================================================
if defined INTERACTIVE (
    pause
    goto :INTERACTIVE_MENU
)
exit /b 0

:DO_START
echo ====================================================
echo Starting all SIMS services...
echo ====================================================

:: 1. Ensure any previous stuck processes or ports are cleanly released first
call :DO_STOP_SILENT

:: 2. Try starting via Windows Task Scheduler first (if registered)
set "TASK_STARTED=0"
schtasks /query /tn "SIMS-Web" >nul 2>&1
if %errorLevel% equ 0 (
    schtasks /run /tn "SIMS-Web" >nul 2>&1
    schtasks /run /tn "SIMS-Queue" >nul 2>&1
    schtasks /run /tn "SIMS-Scheduler" >nul 2>&1
    set "TASK_STARTED=1"
)

:: 3. If not registered in Task Scheduler, start native background runner directly
if "%TASK_STARTED%"=="0" (
    if exist "%SERVICES_DIR%\run-web.bat" (
        start "" /min "%SERVICES_DIR%\run-web.bat"
    )
)

:: 4. Poll for web server port readiness (up to 12 attempts = 6 seconds)
set "IS_ONLINE=0"
for /l %%i in (1, 1, 12) do (
    if "!IS_ONLINE!"=="0" (
        ping 127.0.0.1 -n 2 >nul
        netstat -ano -p tcp 2>nul | findstr /R /C:":443 .*LISTENING" >nul 2>&1
        if !errorLevel! equ 0 set "IS_ONLINE=1"
        if "!IS_ONLINE!"=="0" (
            netstat -ano -p tcp 2>nul | findstr /R /C:":80 .*LISTENING" >nul 2>&1
            if !errorLevel! equ 0 set "IS_ONLINE=1"
        )
        if "!IS_ONLINE!"=="0" (
            netstat -ano -p tcp 2>nul | findstr /R /C:":8000 .*LISTENING" >nul 2>&1
            if !errorLevel! equ 0 set "IS_ONLINE=1"
        )
    )
)

:: 5. Fallback: If still offline, trigger direct background launch
if "!IS_ONLINE!"=="0" (
    if exist "%SERVICES_DIR%\run-web.bat" (
        echo [INFO] Initiating direct background fallback startup...
        start "" /min "%SERVICES_DIR%\run-web.bat"
        for /l %%j in (1, 1, 8) do (
            if "!IS_ONLINE!"=="0" (
                ping 127.0.0.1 -n 2 >nul
                netstat -ano -p tcp 2>nul | findstr /R /C:":443 .*LISTENING" >nul 2>&1
                if !errorLevel! equ 0 set "IS_ONLINE=1"
                if "!IS_ONLINE!"=="0" (
                    netstat -ano -p tcp 2>nul | findstr /R /C:":80 .*LISTENING" >nul 2>&1
                    if !errorLevel! equ 0 set "IS_ONLINE=1"
                )
            )
        )
    )
)

if "!IS_ONLINE!"=="1" goto :START_FINISH

echo [WARNING] Web server did not report online within expected timeframe.
if exist "%APP_DIR%\storage\logs\web-server.log" (
    echo Recent web server log output:
    powershell -NoProfile -Command "Get-Content '%APP_DIR%\storage\logs\web-server.log' -Tail 8 -ErrorAction SilentlyContinue"
)
goto :START_FINISH

:START_FINISH
call :DETECT_LAN_IP
echo.
echo [OK] All SIMS services are active!
echo.
echo Primary Access URLs (This Computer):
echo   - [HTTPS] https://localhost         (Secured with Local Certificate)
echo   - [HTTP]  http://localhost          (Redirects to HTTPS)
echo.
echo Local School Network (LAN) Access (Other Devices / Wi-Fi):
echo   - [Recommended]  http://!LAN_IP!       (Direct HTTP - no cert warnings on phones)
echo   - [Secure HTTPS] https://!LAN_IP!      (Encrypted HTTPS)
echo   - [Device Name]  http://%COMPUTERNAME%
echo   - [mDNS Domain]  http://%COMPUTERNAME%.local
echo ====================================================
if defined INTERACTIVE (
    pause
    goto :INTERACTIVE_MENU
)
exit /b 0

:DO_RESTART
echo ====================================================
echo Restarting all SIMS services...
echo ====================================================
call :DO_STOP_SILENT
call :DO_START
exit /b 0

:DETECT_LAN_IP
if defined LAN_IP goto :eof
set "LAN_IP="
for /f "tokens=2 delims=:" %%I in ('ipconfig ^| findstr /i "IPv4" 2^>nul') do (
    if not defined LAN_IP (
        set "IP_CANDIDATE=%%I"
        set "IP_CANDIDATE=!IP_CANDIDATE: =!"
        if not "!IP_CANDIDATE!"=="" (
            if not "!IP_CANDIDATE:~0,4!"=="127." (
                if not "!IP_CANDIDATE:~0,8!"=="169.254." (
                    set "LAN_IP=!IP_CANDIDATE!"
                )
            )
        )
    )
)
if not defined LAN_IP set "LAN_IP=127.0.0.1"
goto :eof
