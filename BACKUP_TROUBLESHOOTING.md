# BoardEase Auto Backup Troubleshooting Guide

## Problem
Auto backup stopped working after November 9th, 2025, despite having Windows Task Scheduler configured.

## Root Cause
The `run_backup.bat` file contained a `pause` command that waits for user input. When Task Scheduler runs the batch file in the background, it waits indefinitely for input that never comes, causing the task to hang or fail silently.

## Solution Applied

### 1. Fixed `run_backup.bat`
- **Removed `pause` command** - This was blocking Task Scheduler
- **Added proper error handling** - Script now exits with error codes
- **Added logging** - All output is logged to `logs/backup.log`
- **Used full PHP path** - Uses `C:\xampp\php\php.exe` or falls back to system PHP
- **Added success/failure detection** - Script checks exit codes

### 2. Enhanced `auto_backup.php`
- **Added comprehensive logging** - All backup operations are logged
- **Improved error handling** - Better exception handling and error messages
- **Added file size reporting** - Shows backup file size in MB
- **Better cleanup logging** - Logs when old backups are deleted

## How to Fix Task Scheduler

### Step 1: Update Task Scheduler Configuration

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, press Enter
   - Or search for "Task Scheduler" in Start menu

2. **Find Your Backup Task**
   - Look for "BoardEase Database Backup" or "BoardEase Auto Backup"
   - Right-click on it and select **Properties**

3. **Update the Action**
   - Go to the **Actions** tab
   - Click on the existing action and click **Edit**
   - **Program/script:** `C:\xampp\htdocs\BoardEase2\run_backup.bat`
   - **Start in (optional):** `C:\xampp\htdocs\BoardEase2`
   - Click **OK**

4. **Check Triggers**
   - Go to the **Triggers** tab
   - Make sure there's a trigger set to run daily (or as needed)
   - **Recommended:** Daily at 11:55 PM (or any time you prefer)
   - Click **Edit** to modify if needed
   - Make sure **Enabled** is checked
   - Click **OK**

5. **Update General Settings**
   - Go to the **General** tab
   - Make sure **"Run whether user is logged on or not"** is selected
   - Check **"Run with highest privileges"** (recommended)
   - **Configure for:** Windows 10 (or your Windows version)
   - Click **OK**

6. **Enable History (Important!)**
   - Go to the **History** tab
   - Make sure **"Enable All Tasks History"** is checked
   - This will allow you to see what happened when the task runs
   - Click **OK**

7. **Update Settings**
   - Go to the **Settings** tab
   - **Allow task to be run on demand:** Checked
   - **Run task as soon as possible after a scheduled start is missed:** Checked
   - **If the task fails, restart every:** 10 minutes
   - **Attempt to restart up to:** 3 times
   - **Stop the task if it runs longer than:** Unchecked (or set to 1 hour)
   - **If the running task does not end when requested, force it to stop:** Checked
   - Click **OK**

### Step 2: Test the Backup Manually

1. **Test the Batch File**
   - Open Command Prompt (as Administrator)
   - Navigate to: `cd C:\xampp\htdocs\BoardEase2`
   - Run: `run_backup.bat`
   - Check if backup is created in `backups` folder
   - Check `logs/backup.log` for any errors

2. **Test the PHP Script Directly**
   - Open Command Prompt
   - Navigate to: `cd C:\xampp\htdocs\BoardEase2`
   - Run: `C:\xampp\php\php.exe auto_backup.php`
   - Check if backup is created
   - Check `logs/backup.log` for output

3. **Run Task Manually in Task Scheduler**
   - Open Task Scheduler
   - Find your backup task
   - Right-click and select **Run**
   - Wait a few seconds
   - Right-click and select **End** (to stop if it's hanging)
   - Check **History** tab to see what happened
   - Check `logs/backup.log` for output
   - Check `backups` folder for new backup file

### Step 3: Verify Backup is Working

1. **Check Backup Files**
   - Navigate to: `C:\xampp\htdocs\BoardEase2\backups`
   - You should see a new backup file: `boardease_auto_backup_YYYY-MM-DD_HH-MM-SS.sql`
   - Check the file size (should be several MB)
   - Check the file date/time (should be recent)

2. **Check Log Files**
   - Navigate to: `C:\xampp\htdocs\BoardEase2\logs`
   - Open `backup.log`
   - You should see entries like:
     ```
     [2025-11-15 23:55:00] Starting database backup...
     [2025-11-15 23:55:01] Auto backup process started
     [2025-11-15 23:55:05] Auto backup created successfully: backups/boardease_auto_backup_2025-11-15_23-55-05.sql (Size: 2.5 MB)
     [2025-11-15 23:55:06] Auto backup process completed successfully
     [2025-11-15 23:55:06] Backup completed successfully!
     ```

3. **Check Task Scheduler History**
   - Open Task Scheduler
   - Find your backup task
   - Click on **History** tab
   - You should see entries showing:
     - Task started
     - Action started
     - Action completed
     - Task completed

## Common Issues and Solutions

### Issue 1: Task Runs But No Backup Created
**Solution:**
- Check `logs/backup.log` for errors
- Verify PHP path is correct: `C:\xampp\php\php.exe`
- Verify database credentials in `dbConfig.php`
- Check if XAMPP MySQL is running
- Verify write permissions on `backups` folder

### Issue 2: Task Shows "Running" But Never Completes
**Solution:**
- This was caused by the `pause` command (now fixed)
- If still happening, check if PHP is hanging
- Check Task Scheduler **Settings** tab → **Stop the task if it runs longer than:** Set to 1 hour
- Check `logs/backup.log` to see where it's stuck

### Issue 3: Task Doesn't Run at All
**Solution:**
- Check if trigger is enabled
- Check if task is enabled
- Verify user account has permissions
- Check **Conditions** tab → Make sure **"Start the task only if the computer is on AC power"** is unchecked
- Check **Settings** tab → Make sure **"Allow task to be run on demand"** is checked

### Issue 4: "Access Denied" Error
**Solution:**
- Run Task Scheduler as Administrator
- In **General** tab → Check **"Run with highest privileges"**
- Verify user account has permissions to:
  - Run batch files
  - Access `C:\xampp\htdocs\BoardEase2`
  - Write to `backups` folder
  - Write to `logs` folder

### Issue 5: PHP Not Found
**Solution:**
- Update `run_backup.bat` to use full path: `C:\xampp\php\php.exe`
- Verify XAMPP is installed at `C:\xampp`
- If XAMPP is in different location, update the path in `run_backup.bat`

## Manual Backup Test

To test if the backup works manually:

1. **Open Command Prompt**
2. **Navigate to project folder:**
   ```
   cd C:\xampp\htdocs\BoardEase2
   ```
3. **Run the backup script:**
   ```
   C:\xampp\php\php.exe auto_backup.php
   ```
4. **Check for backup file:**
   - Look in `backups` folder
   - File should be named: `boardease_auto_backup_YYYY-MM-DD_HH-MM-SS.sql`
5. **Check log file:**
   - Look in `logs/backup.log`
   - Should show successful backup message

## Monitoring Backup Status

### Daily Check
1. Check `backups` folder for new backup file
2. Check `logs/backup.log` for any errors
3. Check Task Scheduler **History** tab

### Weekly Check
1. Verify backup files are being created daily
2. Check backup file sizes (should be consistent)
3. Test restoring a backup to verify it's valid
4. Check if old backups are being deleted (after 7 days)

## Backup File Location

- **Backup files:** `C:\xampp\htdocs\BoardEase2\backups\`
- **Log files:** `C:\xampp\htdocs\BoardEase2\logs\backup.log`
- **Backup script:** `C:\xampp\htdocs\BoardEase2\auto_backup.php`
- **Batch file:** `C:\xampp\htdocs\BoardEase2\run_backup.bat`

## Backup Retention

- Backups are kept for **7 days**
- Older backups are automatically deleted
- To keep backups longer, modify `auto_backup.php`:
  ```php
  $cutoff = time() - (7 * 24 * 60 * 60); // Change 7 to desired days
  ```

## Restoring a Backup

1. **Stop XAMPP MySQL** (if running)
2. **Open phpMyAdmin** or MySQL command line
3. **Drop the database:**
   ```sql
   DROP DATABASE boardease2;
   ```
4. **Create the database:**
   ```sql
   CREATE DATABASE boardease2;
   ```
5. **Import the backup:**
   - In phpMyAdmin: Select database → Import → Choose backup file
   - Or use command line:
     ```
     mysql -u boardease -p boardease2 < backups/boardease_auto_backup_YYYY-MM-DD_HH-MM-SS.sql
     ```

## Need Help?

If you're still having issues:

1. **Check the log file:** `logs/backup.log`
2. **Check Task Scheduler History:** Look for error messages
3. **Test manually:** Run `run_backup.bat` from Command Prompt
4. **Verify PHP path:** Make sure `C:\xampp\php\php.exe` exists
5. **Verify database:** Make sure MySQL is running and accessible
6. **Check permissions:** Make sure the user account has necessary permissions

---

**Last Updated:** 2025-11-15
**Status:** Fixed - Backup should now work with Task Scheduler


