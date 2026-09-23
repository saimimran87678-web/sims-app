@echo off
setlocal

:: 0. Force working directory to script location
cd /d "%~dp0"
title SIMS Automated System Installer

echo ====================================================
echo      SIMS School Management System Installer     
echo ====================================================
echo.

:: 1. Check for Administrator Privileges
net session >nul 2>&1
if %errorLevel% equ 0 goto :ADMIN_OK

echo [ERROR] Administrator privileges are required!
echo.
echo This installer must be run as Administrator to configure
echo network ports (80 / 443) and Windows background services.
echo.
echo Please close this window, then:
echo   Right-click install.bat and select "Run as administrator"
echo.
echo ====================================================
pause
exit /b 1

:ADMIN_OK
echo [OK] Running with Administrator privileges.

:: 2. Resolve script, root, and runtime directories (2 levels up from scripts\windows\)
for %%I in ("%~dp0..\..") do set "ROOT_DIR=%%~fI"
if "%ROOT_DIR:~-1%"=="\" set "ROOT_DIR=%ROOT_DIR:~0,-1%"

set "APP_DIR=%ROOT_DIR%\sims-app"
set "SERVICES_DIR=%~dp0services"
set "PORTABLE_PHP=%ROOT_DIR%\runtime\php\php.exe"

if exist "%PORTABLE_PHP%" goto :USE_PORTABLE_PHP
where php >nul 2>&1
if %errorLevel% equ 0 goto :USE_SYSTEM_PHP

echo.
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
:: 3. Free up Port 80 and 443 from Windows IIS if active
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

netsh advfirewall firewall delete rule name="SIMS-Web-HTTP" >nul 2>&1
netsh advfirewall firewall add rule name="SIMS-Web-HTTP" dir=in action=allow protocol=TCP localport=80 >nul 2>&1
netsh advfirewall firewall delete rule name="SIMS-Web-HTTPS" >nul 2>&1
netsh advfirewall firewall add rule name="SIMS-Web-HTTPS" dir=in action=allow protocol=TCP localport=443 >nul 2>&1
echo [OK] Network ports and firewall configured.

:: 4. Ensure .env file is present
if exist "%APP_DIR%\.env" goto :ENV_OK
if not exist "%APP_DIR%\.env.example" goto :ENV_MISSING
echo [INFO] .env file not found. Creating from .env.example...
copy /y "%APP_DIR%\.env.example" "%APP_DIR%\.env" >nul
echo [OK] Created .env file from .env.example.
goto :ENV_OK

:ENV_MISSING
echo.
echo [ERROR] Neither .env nor .env.example was found in:
echo   %APP_DIR%
echo Please ensure the release package was extracted properly.
pause
exit /b 1

:ENV_OK
:: Ensure vendor dependencies are present
if exist "%APP_DIR%\vendor\autoload.php" goto :VENDOR_OK
echo.
echo [ERROR] Application vendor dependencies are missing!
echo Missing: %APP_DIR%\vendor\autoload.php
echo Please ensure you are running the standalone release package with bundled dependencies.
echo.
pause
exit /b 1

:VENDOR_OK
:: Check compiled frontend assets
if exist "%APP_DIR%\public\build\manifest.json" goto :ASSETS_OK
echo [WARNING] Compiled frontend assets not found at public\build\manifest.json!
echo Web interface styles may not render properly.

:ASSETS_OK
echo.
echo [2/4] Performing initial database and cache setup...
cd /d "%APP_DIR%"

del /q /f "%APP_DIR%\bootstrap\cache\*.php" >nul 2>&1
del /q /f "%APP_DIR%\storage\framework\views\*.php" >nul 2>&1
"%PHP_BIN%" artisan config:clear >nul 2>&1
"%PHP_BIN%" artisan route:clear >nul 2>&1
"%PHP_BIN%" artisan view:clear >nul 2>&1
"%PHP_BIN%" artisan cache:clear >nul 2>&1

"%PHP_BIN%" artisan sims:install
if %errorLevel% equ 0 goto :INSTALL_OK
echo.
echo [ERROR] Initial installation failed!
echo Check the error messages above for details.
echo.
pause
exit /b 1

:INSTALL_OK
echo.
echo [3/4] Registering background services in Windows Task Scheduler...
"%PHP_BIN%" artisan sims:setup-windows
if %errorLevel% neq 0 (
    echo [WARNING] Background service registration reported a warning.
)

echo.
echo [4/4] Starting SIMS Web Server and verifying status...
cd /d "%ROOT_DIR%"

:: Terminate any stale web server or FastCGI processes first
schtasks /end /tn "SIMS-Web" >nul 2>&1
schtasks /end /tn "SIMS-Queue" >nul 2>&1
schtasks /end /tn "SIMS-Scheduler" >nul 2>&1
taskkill /F /IM frankenphp.exe /IM php-cgi.exe >nul 2>&1
timeout /t 1 /nobreak >nul

schtasks /run /tn "SIMS-Web" >nul 2>&1
schtasks /run /tn "SIMS-Queue" >nul 2>&1
schtasks /run /tn "SIMS-Scheduler" >nul 2>&1

set "WAIT_TRIES=0"
:CHECK_PORT_LOOP
timeout /t 1 /nobreak >nul
netstat -ano 2>nul | findstr ":80 " >nul 2>&1
if %errorLevel% equ 0 goto :PORT_ONLINE
netstat -ano 2>nul | findstr ":443 " >nul 2>&1
if %errorLevel% equ 0 goto :PORT_ONLINE
set /a WAIT_TRIES+=1
if %WAIT_TRIES% lss 6 goto :CHECK_PORT_LOOP

echo [INFO] Starting web server directly in background...
if exist "%SERVICES_DIR%\run-web.bat" (
    start "" /min "%SERVICES_DIR%\run-web.bat"
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
if exist "%SERVICES_DIR%\trust-cert.bat" (
    echo.
    echo [INFO] Registering local SSL certificate in Windows Trust Store...
    call "%SERVICES_DIR%\trust-cert.bat"
)

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
echo Opening SIMS in your default web browser (HTTPS Secured)...
start https://localhost

echo.
echo Primary Access Addresses:
echo   - [HTTPS] https://localhost         (Secured with Local Certificate)
echo   - [HTTPS] https://sims.local
echo   - [HTTP]  http://localhost          (Redirects to HTTPS)
echo.
echo Local School Network Access:
echo   - [HTTP]  http://%COMPUTERNAME%
echo   - [HTTPS] https://%COMPUTERNAME%.local
echo.
echo Detected Local Network IP Addresses:
ipconfig 2>nul | findstr /i "IPv4"
echo.
pause
