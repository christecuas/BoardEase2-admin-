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















