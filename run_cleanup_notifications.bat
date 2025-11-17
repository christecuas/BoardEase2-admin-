@echo off
REM Batch script to run notification cleanup on Windows
REM This can be scheduled using Windows Task Scheduler

echo ========================================
echo BoardEase Notification Cleanup Script
echo ========================================
echo.

REM Get the directory where this script is located
set SCRIPT_DIR=%~dp0
cd /d "%SCRIPT_DIR%"

REM Find PHP executable
REM Try common PHP installation paths
set PHP_EXE=

REM Check if PHP is in PATH
where php >nul 2>&1
if %ERRORLEVEL% == 0 (
    set PHP_EXE=php
    echo Found PHP in PATH
) else (
    REM Try common installation paths
    if exist "C:\xampp\php\php.exe" (
        set PHP_EXE=C:\xampp\php\php.exe
        echo Found PHP at: %PHP_EXE%
    ) else if exist "C:\wamp64\bin\php\php8.2.0\php.exe" (
        set PHP_EXE=C:\wamp64\bin\php\php8.2.0\php.exe
        echo Found PHP at: %PHP_EXE%
    ) else if exist "C:\php\php.exe" (
        set PHP_EXE=C:\php\php.exe
        echo Found PHP at: %PHP_EXE%
    ) else (
        echo ERROR: PHP not found!
        echo Please install PHP or update this script with your PHP path.
        pause
        exit /b 1
    )
)

echo.
echo Running notification cleanup...
echo.

REM Run the cleanup script
"%PHP_EXE%" cleanup_old_notifications.php

if %ERRORLEVEL% == 0 (
    echo.
    echo ========================================
    echo Cleanup completed successfully!
    echo ========================================
) else (
    echo.
    echo ========================================
    echo ERROR: Cleanup failed!
    echo ========================================
)

echo.
pause

