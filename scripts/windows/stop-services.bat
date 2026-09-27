@echo off
setlocal
cd /d "%~dp0"
title Stop SIMS Services

net session >nul 2>&1
if %errorLevel% equ 0 goto :ADMIN_OK

echo [ERROR] Administrator privileges are required to stop background services.
echo Please right-click stop-services.bat and select "Run as administrator".
echo.
pause
exit /b 1

:ADMIN_OK
echo ====================================================
echo         Stopping All SIMS Windows Services
echo ====================================================
echo.

echo [1/3] Stopping background scheduled tasks...
schtasks /end /tn "SIMS-Web" >nul 2>&1
schtasks /end /tn "SIMS-Queue" >nul 2>&1
schtasks /end /tn "SIMS-Scheduler" >nul 2>&1

echo [2/3] Terminating PHP, FrankenPHP, and Control Center processes...
taskkill /F /T /IM frankenphp.exe >nul 2>&1
taskkill /F /T /IM php-cgi.exe >nul 2>&1
taskkill /F /T /IM php.exe >nul 2>&1
taskkill /F /T /IM Adminova-Control-Center.exe >nul 2>&1

echo [3/3] Freeing network listening ports (443, 80, 8000, 9000, 9001)...
for %%P in (443 80 8000 9000 9001) do (
    for /f "tokens=5" %%a in ('netstat -aon 2^>nul ^| findstr ":%%P "') do (
        if not "%%a"=="0" taskkill /F /PID %%a >nul 2>&1
    )
)
ping 127.0.0.1 -n 2 >nul

echo.
echo ====================================================
echo  [OK] All SIMS services and network ports are released.
echo       You can now safely move, edit, update, or delete files.
echo ====================================================
echo.
pause
