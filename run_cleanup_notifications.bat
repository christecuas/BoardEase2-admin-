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
echo Waiting for MySQL connection...
echo.

REM Wait for MySQL to be ready (check every 15 seconds, max 5 minutes)
set MAX_WAIT=20
set WAIT_COUNT=0
set WAIT_INTERVAL=15

:WAIT_FOR_MYSQL
"%PHP_EXE%" check_mysql_connection.php >nul 2>&1
if %ERRORLEVEL% == 0 (
    echo MySQL is ready! Proceeding with cleanup...
    echo.
    goto MYSQL_READY
)

set /a WAIT_COUNT+=1
if %WAIT_COUNT% GEQ %MAX_WAIT% (
    echo.
    echo ========================================
    echo ERROR: MySQL not available after 5 minutes!
    echo ========================================
    echo Please start MySQL/XAMPP service manually.
    echo.
    pause
    exit /b 1
)

echo Waiting for MySQL... (%WAIT_COUNT%/%MAX_WAIT%)
timeout /t %WAIT_INTERVAL% /nobreak >nul
goto WAIT_FOR_MYSQL

:MYSQL_READY

REM Create logs directory if it doesn't exist
if not exist "logs" mkdir logs

REM Get current date/time for log filename
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set logfile=logs\notification_cleanup_%datetime:~0,8%_%datetime:~8,6%.log

REM Run the cleanup script and log output
echo [%date% %time%] Starting notification cleanup... >> logs\notification_cleanup.log
echo [%date% %time%] Starting notification cleanup... >> "%logfile%"

REM Run script and capture output to temp file, then append to both log files
"%PHP_EXE%" cleanup_old_notifications.php > "%TEMP%\cleanup_output.tmp" 2>&1
set EXIT_CODE=%errorlevel%

REM Display output first
type "%TEMP%\cleanup_output.tmp"

REM Append output to both log files
type "%TEMP%\cleanup_output.tmp" >> logs\notification_cleanup.log
type "%TEMP%\cleanup_output.tmp" >> "%logfile%"

REM Clean up temp file
del "%TEMP%\cleanup_output.tmp" >nul 2>&1

if %EXIT_CODE% == 0 (
    echo.
    echo ========================================
    echo Cleanup completed successfully!
    echo ========================================
    echo [%date% %time%] Cleanup completed successfully! >> logs\notification_cleanup.log
    echo [%date% %time%] Cleanup completed successfully! >> "%logfile%"
) else (
    echo.
    echo ========================================
    echo ERROR: Cleanup failed!
    echo ========================================
    echo [%date% %time%] Cleanup FAILED with error code %EXIT_CODE% >> logs\notification_cleanup.log
    echo [%date% %time%] Cleanup FAILED with error code %EXIT_CODE% >> "%logfile%"
)

echo.
echo Logs saved to: logs\notification_cleanup.log
echo Daily log: %logfile%
echo.
pause


