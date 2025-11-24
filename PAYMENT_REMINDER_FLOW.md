# Payment Reminder Flow Explanation

## Overview
The payment reminder system sends notifications **EVERY DAY** starting from 5 days before the payment due date until the due date itself.

## Reminder Schedule

The system sends reminders on the following days:

| Days Before Due Date | Reminder Type | When It's Sent |
|----------------------|---------------|----------------|
| 5 days before | `5_days_before` | 5 days before due date |
| 4 days before | `4_days_before` | 4 days before due date |
| 3 days before | `3_days_before` | 3 days before due date |
| 2 days before | `2_days_before` | 2 days before due date |
| 1 day before | `1_day_before` | 1 day before due date |
| On due date | `due_date` | On the due date itself |

## Example Flow

If a payment is due on **December 1, 2025**, reminders will be sent:

- **November 26, 2025** (5 days before) - "Payment due in 5 days"
- **November 27, 2025** (4 days before) - "Payment due in 4 days"
- **November 28, 2025** (3 days before) - "Payment due in 3 days"
- **November 29, 2025** (2 days before) - "Payment due in 2 days"
- **November 30, 2025** (1 day before) - "Payment due in 1 day"
- **December 1, 2025** (due date) - "Payment is due today"

## How It Works

1. **Daily Execution**: The script runs once per day at 10:00 AM (via Windows Task Scheduler)

2. **Query Payment Breakdowns**: 
   - Finds all unpaid payment breakdowns
   - Where due date is between today and 5 days from today
   - Excludes paid, cancelled, or overdue payments

3. **Calculate Days Until Due**:
   - Calculates how many days until each payment's due date
   - Only processes payments with 0-5 days remaining

4. **Check for Duplicates**:
   - Checks `payment_reminder_logs` table to see if a reminder was already sent today
   - Prevents sending duplicate reminders on the same day

5. **Send Notifications**:
   - Creates in-app notification in the `notifications` table
   - Sends push notification via FCM (Firebase Cloud Messaging)
   - Logs the reminder in `payment_reminder_logs` table

## Notification Messages

The notification message varies based on days until due:

- **5 days before**: "Reminder: Your payment of ₱X,XXX.XX for [Period Label] is due in 5 days (Due: [Date])."
- **4 days before**: "Reminder: Your payment of ₱X,XXX.XX for [Period Label] is due in 4 days (Due: [Date])."
- **3 days before**: "Reminder: Your payment of ₱X,XXX.XX for [Period Label] is due in 3 days (Due: [Date])."
- **2 days before**: "Reminder: Your payment of ₱X,XXX.XX for [Period Label] is due in 2 days (Due: [Date])."
- **1 day before**: "Reminder: Your payment of ₱X,XXX.XX for [Period Label] is due in 1 day (Due: [Date])."
- **Due date**: "Reminder: Your payment of ₱X,XXX.XX for [Period Label] is due today ([Date]). Please make your payment to avoid late fees."

## Why No Reminders Were Sent

If the log shows "Sent 0 reminders", possible reasons:

1. **No payment breakdowns with due dates in the next 5 days**
   - Check if there are any unpaid payment breakdowns
   - Verify that breakdowns have `due_date` set (or `period_start_date` as fallback)

2. **All payments are already paid**
   - The script only processes `is_paid = 0` breakdowns

3. **Payments are cancelled**
   - Cancelled payments are excluded

4. **Due dates are more than 5 days away**
   - Reminders only start 5 days before due date

5. **Reminders already sent today**
   - The system prevents duplicate reminders on the same day

## Debugging

To see why no reminders were sent, check the log file:
- Location: `logs/payment_reminders.log`
- Look for messages like:
  - "Found X payment breakdowns to check"
  - "Breakdown details:"
  - "Skipping breakdown ID X: Days until due (X) is outside reminder range"

You can also manually test by running:
```bash
php auto_notify_payment_reminders.php
```

## Database Tables

### payment_breakdowns
- Contains payment breakdowns with due dates
- Fields used: `breakdown_id`, `booking_id`, `due_date`, `period_start_date`, `amount`, `period_label`, `is_paid`, `payment_status`

### payment_reminder_logs
- Tracks which reminders have been sent
- Prevents duplicate reminders
- Fields: `breakdown_id`, `user_id`, `reminder_type`, `due_date`, `reminder_date`, `notif_id`, `fcm_sent`

### notifications
- Stores in-app notifications
- Created by the reminder system
- Fields: `notif_id`, `user_id`, `notif_title`, `notif_message`, `notif_type`, `notif_status`

## Important Notes

- Reminders are sent **once per day** at 10:00 AM
- Each reminder type (5_days_before, 4_days_before, etc.) is sent only once per breakdown
- If a payment is made, reminders stop automatically (because `is_paid = 1`)
- The system uses `due_date` from payment_breakdowns, or falls back to `period_start_date` if due_date is NULL


