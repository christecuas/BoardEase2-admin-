# Admin Notifications System - Setup and Testing Guide

## Overview
The admin dashboard now displays real-time system notifications including new user registrations, new boarding houses, payment disputes, and more.

## How It Works

### 1. System Notifications Display
When an admin opens the **Notifications** section in the admin dashboard:
- The system automatically loads system notifications
- Shows pending user registrations
- Shows recent boarding houses (last 7 days)
- Shows payment disputes
- Shows large payments (over ₱10,000)
- Shows completed maintenance requests
- Shows flagged accounts

### 2. User Registration Flow
1. User registers via mobile app
2. Registration is created with `status = 'pending'`
3. Admin opens admin dashboard → Notifications section
4. System notifications tab shows: "New User Registration - [Name] registered as a [Role]"
5. Admin can see all pending registrations that need approval

### 3. Testing the System

#### Test User Registration Notification:
1. Register a new user via the mobile app (or insert test data)
2. Open admin dashboard
3. Click "Notifications" in the sidebar
4. Click "System Notifications" tab
5. You should see: "New User Registration - [Name] registered as a [Role]"

#### Test Admin Sending Notifications:
1. Open admin dashboard
2. Go to Notifications section
3. Click "Compose Notification" tab
4. Select recipients (All Users, Boarders, or Owners)
5. Enter title and message
6. Click "Send Notification"
7. All selected users will receive the notification (database + FCM push)

## API Endpoints

### Get System Notifications
```
GET get_admin_notifications.php?action=system&limit=50
```

### Send Notification (Admin)
```
POST get_admin_notifications.php?action=send
Body: {
    "title": "Notification Title",
    "message": "Notification Message",
    "recipients": "all|boarders|owners",
    "notification_type": "announcement|maintenance|general"
}
```

## Files Involved

1. **admin_system_notifications.php** - System notifications helper
2. **get_admin_notifications.php** - API endpoint for admin notifications
3. **activity_notifications.php** - Activity notification system
4. **notification_helper.php** - Notification helper with FCM support
5. **html/admin_dashboard.php** - Admin dashboard with JavaScript

## Database Queries

### Pending Registrations
```sql
SELECT * FROM registrations WHERE status = 'pending'
```

### Recent Boarding Houses
```sql
SELECT * FROM boarding_houses 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
```

## Troubleshooting

### System notifications not showing?
1. Check if there are pending registrations: `SELECT * FROM registrations WHERE status = 'pending'`
2. Check browser console for JavaScript errors
3. Verify `get_admin_notifications.php?action=system` returns data
4. Check database connection in `db_helper.php`

### Notifications not sending?
1. Verify FCM configuration in `fcm_config.php`
2. Check device tokens are registered
3. Verify user status is 'Active' and registration status is 'approved'
4. Check error logs in PHP error log

## Next Steps

- [ ] Test with real user registration
- [ ] Verify system notifications appear in admin dashboard
- [ ] Test sending notifications from admin dashboard
- [ ] Verify FCM push notifications are received on mobile devices





