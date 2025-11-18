@echo off
REM BoardEase Auto Backup Script for Windows Task Scheduler
REM This script runs the PHP backup script without user interaction

REM Set error handling
setlocal enabledelayedexpansion

REM Change to the project directory
cd /d C:\xampp\htdocs\BoardEase2

REM Check if PHP is available (try XAMPP PHP first, then system PHP)
set PHP_PATH=C:\xampp\php\php.exe
if not exist "%PHP_PATH%" (
    set PHP_PATH=php
)

REM Create logs directory if it doesn't exist
if not exist "logs" mkdir logs

REM Wait for MySQL to be ready (check every 15 seconds, max 5 minutes)
echo [%date% %time%] Waiting for MySQL connection... >> logs\backup.log

set MAX_WAIT=20
set WAIT_COUNT=0
set WAIT_INTERVAL=15

:WAIT_FOR_MYSQL
"%PHP_PATH%" check_mysql_connection.php > "%TEMP%\mysql_check_backup.tmp" 2>&1
if %ERRORLEVEL% == 0 (
    echo [%date% %time%] MySQL is ready! Proceeding with backup... >> logs\backup.log
    del "%TEMP%\mysql_check_backup.tmp" >nul 2>&1
    goto MYSQL_READY
)

set /a WAIT_COUNT+=1
if %WAIT_COUNT% GEQ %MAX_WAIT% (
    echo [%date% %time%] ERROR: MySQL not available after 5 minutes. Backup aborted. >> logs\backup.log
    type "%TEMP%\mysql_check_backup.tmp" >> logs\backup.log
    del "%TEMP%\mysql_check_backup.tmp" >nul 2>&1
    exit /b 1
)

echo [%date% %time%] MySQL not ready yet. Waiting... (%WAIT_COUNT%/%MAX_WAIT%) >> logs\backup.log
timeout /t %WAIT_INTERVAL% /nobreak >nul
goto WAIT_FOR_MYSQL

:MYSQL_READY
del "%TEMP%\mysql_check_backup.tmp" >nul 2>&1

REM Run the backup script and log output
echo [%date% %time%] Starting database backup... >> logs\backup.log

REM Execute PHP backup script
"%PHP_PATH%" auto_backup.php >> logs\backup.log 2>&1

REM Check if backup was successful (exit code 0 = success)
if %ERRORLEVEL% EQU 0 (
    echo [%date% %time%] Backup completed successfully! >> logs\backup.log
    exit /b 0
) else (
    echo [%date% %time%] Backup FAILED with error code %ERRORLEVEL% >> logs\backup.log
    exit /b %ERRORLEVEL%
)

