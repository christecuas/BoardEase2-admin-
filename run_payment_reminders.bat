@echo off
REM Batch file to run auto_notify_payment_reminders.php via Windows Task Scheduler
REM This ensures proper logging and error handling
REM Schedule this to run daily at 10:00 AM

REM Set the working directory to the script location
cd /d C:\xampp\htdocs\BoardEase2

REM Find PHP executable (try common locations)
set PHP_EXEC=
if exist "C:\xampp\php\php.exe" set PHP_EXEC=C:\xampp\php\php.exe
if exist "C:\wamp64\bin\php\php8.1.0\php.exe" set PHP_EXEC=C:\wamp64\bin\php\php8.1.0\php.exe
if exist "C:\wamp\bin\php\php8.1.0\php.exe" set PHP_EXEC=C:\wamp\bin\php\php8.1.0\php.exe
if exist "C:\php\php.exe" set PHP_EXEC=C:\php\php.exe
if exist "C:\Program Files\PHP\php.exe" set PHP_EXEC=C:\Program Files\PHP\php.exe

REM If PHP not found in common locations, try to find it in PATH
if "%PHP_EXEC%"=="" (
    where php >nul 2>&1
    if %errorlevel%==0 (
        set PHP_EXEC=php
    )
)

REM If still not found, show error
if "%PHP_EXEC%"=="" (
    echo ERROR: PHP executable not found!
    echo Please edit this batch file and set PHP_EXEC to your PHP path
    echo Example: set PHP_EXEC=C:\xampp\php\php.exe
    exit /b 1
)

REM Create logs directory if it doesn't exist
if not exist "logs" mkdir logs

REM Get current date/time for log filename
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set logfile=logs\payment_reminders_%datetime:~0,8%_%datetime:~8,6%.log

REM Wait for MySQL to be ready (check every 15 seconds, max 5 minutes)
echo [%date% %time%] Waiting for MySQL connection... >> "logs\payment_reminders.log"
echo [%date% %time%] Waiting for MySQL connection... >> "%logfile%"

set MAX_WAIT=20
set WAIT_COUNT=0
set WAIT_INTERVAL=15

:WAIT_FOR_MYSQL
"%PHP_EXEC%" check_mysql_connection.php > "%TEMP%\mysql_check.tmp" 2>&1
if %ERRORLEVEL% == 0 (
    echo [%date% %time%] MySQL is ready! Proceeding... >> "logs\payment_reminders.log"
    echo [%date% %time%] MySQL is ready! Proceeding... >> "%logfile%"
    del "%TEMP%\mysql_check.tmp" >nul 2>&1
    goto MYSQL_READY
)

set /a WAIT_COUNT+=1
if %WAIT_COUNT% GEQ %MAX_WAIT% (
    echo [%date% %time%] ERROR: MySQL not available after 5 minutes. Exiting. >> "logs\payment_reminders.log"
    echo [%date% %time%] ERROR: MySQL not available after 5 minutes. Exiting. >> "%logfile%"
    type "%TEMP%\mysql_check.tmp" >> "logs\payment_reminders.log"
    type "%TEMP%\mysql_check.tmp" >> "%logfile%"
    del "%TEMP%\mysql_check.tmp" >nul 2>&1
    exit /b 1
)

echo [%date% %time%] MySQL not ready yet. Waiting... (%WAIT_COUNT%/%MAX_WAIT%) >> "logs\payment_reminders.log"
echo [%date% %time%] MySQL not ready yet. Waiting... (%WAIT_COUNT%/%MAX_WAIT%) >> "%logfile%"
timeout /t %WAIT_INTERVAL% /nobreak >nul
goto WAIT_FOR_MYSQL

:MYSQL_READY
del "%TEMP%\mysql_check.tmp" >nul 2>&1

REM Run the PHP script ONCE and log to both files
echo [%date% %time%] Starting auto_notify_payment_reminders.php >> "logs\payment_reminders.log"
echo [%date% %time%] Starting auto_notify_payment_reminders.php >> "%logfile%"

REM Run script and capture output to temp file, then append to both log files
"%PHP_EXEC%" auto_notify_payment_reminders.php > "%TEMP%\payment_reminders_output.tmp" 2>&1
set EXIT_CODE=%errorlevel%

REM Append output to both log files
type "%TEMP%\payment_reminders_output.tmp" >> "logs\payment_reminders.log"
type "%TEMP%\payment_reminders_output.tmp" >> "%logfile%"

REM Clean up temp file
del "%TEMP%\payment_reminders_output.tmp" >nul 2>&1

REM Exit with the PHP script's exit code
exit /b %EXIT_CODE%




