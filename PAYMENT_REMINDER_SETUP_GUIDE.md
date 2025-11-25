# Payment Reminder System - Windows Task Scheduler Setup Guide

This guide will help you set up the payment reminder system to run automatically every day at 10:00 AM using Windows Task Scheduler.

## Prerequisites

- Windows operating system
- XAMPP (or WAMP) installed and running
- MySQL service running
- PHP executable available

## Step-by-Step Setup

### Step 1: Verify the Batch File Location

1. Navigate to your project folder: `C:\xampp\htdocs\BoardEase2`
2. Verify that `run_payment_reminders.bat` exists in this folder
3. If needed, edit the batch file to ensure the PHP path is correct for your system

### Step 2: Open Task Scheduler

1. Press `Windows Key + R` to open the Run dialog
2. Type `taskschd.msc` and press Enter
3. Task Scheduler will open

### Step 3: Create a New Task

1. In the right panel, click **"Create Task..."** (not "Create Basic Task")
   - Note: "Create Task" gives you more options than "Create Basic Task"

### Step 4: Configure General Settings

1. **General Tab:**
   - **Name:** `BoardEase Payment Reminders`
   - **Description:** `Sends payment reminders daily at 10:00 AM for upcoming payment due dates`
   - **Security options:**
     - ✅ Check **"Run whether user is logged on or not"**
     - ✅ Check **"Run with highest privileges"** (if needed for file access)
     - Select **"Configure for:"** → Choose your Windows version (Windows 10/11)

### Step 5: Set Up the Trigger (Schedule)

1. Click the **"Triggers"** tab
2. Click **"New..."** button
3. Configure the trigger:
   - **Begin the task:** `On a schedule`
   - **Settings:**
     - Select **"Daily"**
     - **Start:** Set to `10:00:00 AM` (or your preferred time)
     - **Recur every:** `1 days`
   - ✅ Check **"Enabled"**
4. Click **"OK"**

### Step 6: Set Up the Action (What to Run)

1. Click the **"Actions"** tab
2. Click **"New..."** button
3. Configure the action:
   - **Action:** `Start a program`
   - **Program/script:** Browse and select: `C:\xampp\htdocs\BoardEase2\run_payment_reminders.bat`
     - Or type: `C:\xampp\htdocs\BoardEase2\run_payment_reminders.bat`
   - **Add arguments (optional):** Leave empty
   - **Start in (optional):** `C:\xampp\htdocs\BoardEase2`
4. Click **"OK"**

### Step 7: Configure Conditions (Optional but Recommended)

1. Click the **"Conditions"** tab
2. Configure:
   - ✅ **"Start the task only if the computer is on AC power"** - Uncheck this (so it runs on battery too)
   - ✅ **"Wake the computer to run this task"** - Check this (if you want it to wake sleeping computer)
   - ✅ **"Start the task only if the following network connection is available"** - Uncheck this (not needed)

### Step 8: Configure Settings (Optional but Recommended)

1. Click the **"Settings"** tab
2. Configure:
   - ✅ **"Allow task to be run on demand"** - Check this (so you can test it manually)
   - ✅ **"Run task as soon as possible after a scheduled start is missed"** - Check this
   - ✅ **"If the task fails, restart every:"** - Check and set to `10 minutes`, retry up to `3 times`
   - **"If the running task does not end when requested, force it to stop"** - Check this
   - **"Stop the task if it runs longer than:"** - Set to `1 hour` (shouldn't take that long)

### Step 9: Save and Test

1. Click **"OK"** to save the task
2. You may be prompted to enter your Windows password (for running when logged off)
3. The task will appear in your Task Scheduler Library

### Step 10: Test the Task Manually

1. In Task Scheduler, find your task: **"BoardEase Payment Reminders"**
2. Right-click on it and select **"Run"**
3. Check the output:
   - Open: `C:\xampp\htdocs\BoardEase2\logs\payment_reminders.log`
   - Verify that the script ran successfully
   - Check for any errors

### Step 11: Verify It's Scheduled Correctly

1. In Task Scheduler, select your task
2. Look at the bottom panel → **"Next Run Time"** should show tomorrow at 10:00 AM
3. Check **"Last Run Time"** after testing

## Troubleshooting

### Task Doesn't Run

1. **Check Task Scheduler History:**
   - In Task Scheduler, enable "View" → "Show History" (if not already enabled)
   - Check the "History" tab for your task to see error messages

2. **Check Logs:**
   - Open: `C:\xampp\htdocs\BoardEase2\logs\payment_reminders.log`
   - Look for error messages

3. **Verify PHP Path:**
   - Edit `run_payment_reminders.bat`
   - Make sure the PHP executable path is correct for your system
   - Common paths:
     - `C:\xampp\php\php.exe`
     - `C:\wamp64\bin\php\php8.1.0\php.exe`

4. **Verify MySQL is Running:**
   - The batch file waits for MySQL, but if MySQL isn't running, it will fail
   - Make sure XAMPP MySQL service is running

5. **Check Permissions:**
   - Right-click the task → Properties
   - Go to "General" tab
   - Try changing "Run whether user is logged on or not" to "Run only when user is logged on"
   - Re-enter your password if prompted

### Task Runs But No Reminders Sent

1. **Check if there are payment breakdowns with due dates:**
   - Run the test script: `php test_payment_reminders.php`
   - Or manually run: `php auto_notify_payment_reminders.php`

2. **Check the database:**
   - Verify payment_reminder_logs table exists
   - Check if reminders were already sent (they won't send duplicates)

3. **Check notification system:**
   - Verify users have device tokens registered
   - Check notification_helper.php is working

### View Task Execution History

1. In Task Scheduler, select your task
2. Click the **"History"** tab at the bottom
3. Look for entries with:
   - ✅ Green checkmark = Success
   - ❌ Red X = Failed
   - Click on entries to see detailed messages

## Manual Testing

You can test the reminder system manually at any time:

### Option 1: Run via Task Scheduler
- Right-click the task → **"Run"**

### Option 2: Run the Batch File Directly
- Double-click `run_payment_reminders.bat` in Windows Explorer

### Option 3: Run PHP Script Directly
- Open Command Prompt
- Navigate to: `cd C:\xampp\htdocs\BoardEase2`
- Run: `php auto_notify_payment_reminders.php`

### Option 4: Use Test Script
- Open Command Prompt
- Navigate to: `cd C:\xampp\htdocs\BoardEase2`
- Run: `php test_payment_reminders.php`

## Schedule Customization

If you want to change the reminder time:

1. Open Task Scheduler
2. Find your task: **"BoardEase Payment Reminders"**
3. Right-click → **"Properties"**
4. Go to **"Triggers"** tab
5. Select the trigger and click **"Edit"**
6. Change the **"Start:"** time
7. Click **"OK"** to save

## Disabling/Enabling the Task

- **To disable:** Right-click task → **"Disable"**
- **To enable:** Right-click task → **"Enable"**

## Removing the Task

If you need to remove the scheduled task:

1. Open Task Scheduler
2. Find your task: **"BoardEase Payment Reminders"**
3. Right-click → **"Delete"**
4. Confirm deletion

## Additional Notes

- The script runs once per day at the scheduled time
- It only sends reminders for payment breakdowns that match the schedule (5, 3, 2, 1 days before, or on due date)
- Duplicate reminders are prevented by the payment_reminder_logs table
- All activities are logged to `logs/payment_reminders.log`
- The script automatically creates the payment_reminder_logs table if it doesn't exist

## Support

If you encounter issues:
1. Check the log file: `logs/payment_reminders.log`
2. Check Task Scheduler History for error messages
3. Verify MySQL and PHP are working correctly
4. Test the script manually to isolate the issue




