@echo off
setlocal enabledelayedexpansion

:: 0. Force working directory to the script folder (CRITICAL for "Run as Administrator")
cd /d "%~dp0"
title SIMS Automated System Installer

echo ====================================================
echo      SIMS School Management System Installer     
echo ====================================================
echo.

:: 1. Check for Administrator Privileges and auto-elevate if needed
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Requesting Administrator privileges for SIMS Installer...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b 0
)

:: 2. Resolve script and runtime directories cleanly without trailing slashes
set "ROOT_DIR=%~dp0"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "APP_DIR=%ROOT_DIR%\sims-app"
set "BIN_DIR=%ROOT_DIR%\bin"
set "PORTABLE_PHP=%ROOT_DIR%\runtime\php\php.exe"

if exist "%PORTABLE_PHP%" goto :USE_PORTABLE_PHP
where php >nul 2>&1
if %errorLevel% equ 0 goto :USE_SYSTEM_PHP

echo [ERROR] PHP runtime not found!
echo Please ensure runtime\php\php.exe exists or install PHP 8.2+.
echo.
pause
exit /b 1

:USE_PORTABLE_PHP
set "PHP_BIN=%PORTABLE_PHP%"
echo [OK] Using bundled portable PHP runtime.
goto :PHP_READY

:USE_SYSTEM_PHP
set "PHP_BIN=php"
echo [OK] Using system PHP runtime.

:PHP_READY
:: 3. Free up Port 80 and 443 from Windows IIS if active (Safe GOTO flow, NO pipes inside parentheses)
echo.
echo [1/4] Checking and configuring network port permissions...
sc query W3SVC >nul 2>&1
if %errorLevel% neq 0 goto :SKIP_IIS
sc query W3SVC 2>nul | findstr /i "RUNNING" >nul 2>&1
if %errorLevel% neq 0 goto :SKIP_IIS

echo [NOTICE] Windows IIS (W3SVC) is currently active on Port 80.
echo [INFO] Stopping IIS to free up Port 80 for SIMS...
net stop W3SVC >nul 2>&1
sc config W3SVC start=disabled >nul 2>&1

:SKIP_IIS
netsh http add urlacl url=http://+:80/ user=Everyone >nul 2>&1
netsh http add urlacl url=https://+:443/ user=Everyone >nul 2>&1

:: Configure Windows Firewall for Port 80 and Port 443
netsh advfirewall firewall delete rule name="SIMS-Web-HTTP" >nul 2>&1
netsh advfirewall firewall add rule name="SIMS-Web-HTTP" dir=in action=allow protocol=TCP localport=80 >nul 2>&1
netsh advfirewall firewall delete rule name="SIMS-Web-HTTPS" >nul 2>&1
netsh advfirewall firewall add rule name="SIMS-Web-HTTPS" dir=in action=allow protocol=TCP localport=443 >nul 2>&1
echo [OK] Network ports and firewall configured.

:: 4. Database, migrations, and cache warming
echo.
echo [2/4] Performing initial database and cache setup...
cd /d "%APP_DIR%"
"%PHP_BIN%" artisan sims:install
if %errorLevel% neq 0 (
    echo.
    echo [ERROR] Initial installation failed!
    echo Check the error messages above for details.
    echo.
    pause
    exit /b 1
)

:: 5. Register background services in Task Scheduler
echo.
echo [3/4] Registering background services in Windows Task Scheduler...
"%PHP_BIN%" artisan sims:setup-windows
if %errorLevel% neq 0 (
    echo [WARNING] Background service registration reported a warning.
)

:: 6. Launch services and verify Web Server is listening before opening browser
echo.
echo [4/4] Starting SIMS Web Server and verifying status...
cd /d "%ROOT_DIR%"
schtasks /run /tn "SIMS-Web" >nul 2>&1
schtasks /run /tn "SIMS-Queue" >nul 2>&1
schtasks /run /tn "SIMS-Scheduler" >nul 2>&1

:: Polling Port with safe labeled loop (no parentheses/pipe conflicts)
set "WAIT_TRIES=0"
:CHECK_PORT_LOOP
timeout /t 1 /nobreak >nul
netstat -ano 2>nul | findstr ":80 " >nul 2>&1
if %errorLevel% equ 0 goto :PORT_ONLINE
netstat -ano 2>nul | findstr ":443 " >nul 2>&1
if %errorLevel% equ 0 goto :PORT_ONLINE
set /a WAIT_TRIES+=1
if %WAIT_TRIES% lss 6 goto :CHECK_PORT_LOOP

:: Fallback: Start web server directly if Task Scheduler took too long
echo [INFO] Starting web server directly in background...
if exist "%BIN_DIR%\run-web.bat" (
    start "" /min "%BIN_DIR%\run-web.bat"
    timeout /t 3 /nobreak >nul
)

:PORT_ONLINE
netstat -ano 2>nul | findstr ":80 " >nul 2>&1
if %errorLevel% equ 0 goto :PRINT_ONLINE_80
netstat -ano 2>nul | findstr ":443 " >nul 2>&1
if %errorLevel% equ 0 goto :PRINT_ONLINE_443
echo [NOTICE] Web server is starting up in the background.
goto :REGISTER_CLI

:PRINT_ONLINE_80
echo [OK] SIMS Web Server is ONLINE and listening on Port 80.
goto :REGISTER_CLI

:PRINT_ONLINE_443
echo [OK] SIMS Web Server is ONLINE and listening on Port 443 (HTTPS).

:REGISTER_CLI
:: 7. Register "sims" global CLI in Windows
setx SIMS_HOME "%ROOT_DIR%" /m >nul 2>&1
if exist "%ROOT_DIR%\sims.bat" (
    copy /y "%ROOT_DIR%\sims.bat" "%WINDIR%\System32\sims.bat" >nul 2>&1
)

echo.
echo ====================================================
echo   SIMS Installation and Service Setup Complete!     
echo ====================================================
echo.
echo Control SIMS anytime from any terminal:
echo   sims status    - Check health of all services
echo   sims activate  - Activate license from .env or terminal
echo   sims stop      - Stop all services and release locks
echo   sims start     - Start all services
echo   sims restart   - Restart all services
echo ====================================================
echo.
echo Opening SIMS in your default web browser (Secure HTTPS)...
start https://localhost

echo.
echo Primary Secure Address:
echo   🔒 https://localhost
echo   🔒 https://sims.local
echo.
echo Local School Network (LAN) Access:
echo   🔒 https://%COMPUTERNAME%.local
echo   🔒 https://%COMPUTERNAME%
echo.
echo Detected Local Network IP Addresses:
ipconfig 2>nul | findstr /i "IPv4"
echo.
pause
