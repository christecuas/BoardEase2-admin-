<?php
// Notification Settings API - Manage notification preferences and templates
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'db_helper.php';

$response = [];

try {
    $db = getDB();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $action = $_GET['action'] ?? 'get_settings';
    
    if ($action === 'get_settings') {
        // Get current notification settings
        $settings = getNotificationSettings($db);
        $response = [
            'success' => true,
            'settings' => $settings
        ];
        
    } elseif ($action === 'save_settings') {
        // Save notification settings
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception("Invalid JSON input");
        }
        
        $result = saveNotificationSettings($db, $input);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => 'Notification settings saved successfully'
            ];
        } else {
            throw new Exception("Failed to save settings");
        }
        
    } elseif ($action === 'test_notification') {
        // Test notification sending
        $input = json_decode(file_get_contents('php://input'), true);
        $type = $input['type'] ?? 'general';
        $message = $input['message'] ?? 'Test notification';
        
        $result = sendTestNotification($db, $type, $message);
        
        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
            'details' => $result['details'] ?? null
        ];
        
    } else {
        throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);

function getNotificationSettings($db) {
    // Get ACTUAL notification settings from the codebase and configuration
    
    // Get notification templates from database
    $templates = getNotificationTemplates($db);
    
    // Check FCM configuration
    $fcmServiceAccountPath = __DIR__ . '/firebase-service-account.json';
    $fcmConfigured = file_exists($fcmServiceAccountPath);
    $fcmProjectId = 'boardease2'; // From fcm_config.php
    
    // Get FCM service account info if available
    $fcmServiceAccountInfo = null;
    if ($fcmConfigured) {
        try {
            $serviceAccount = json_decode(file_get_contents($fcmServiceAccountPath), true);
            $fcmServiceAccountInfo = [
                'project_id' => $serviceAccount['project_id'] ?? $fcmProjectId,
                'client_email' => $serviceAccount['client_email'] ?? 'Not available',
                'file_exists' => true
            ];
        } catch (Exception $e) {
            $fcmServiceAccountInfo = [
                'project_id' => $fcmProjectId,
                'client_email' => 'Error reading file',
                'file_exists' => true,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Check database notifications (always enabled - notifications table exists)
    $dbNotificationsEnabled = false;
    try {
        $result = $db->query("SHOW TABLES LIKE 'notifications'");
        $dbNotificationsEnabled = ($result && $result->num_rows > 0);
    } catch (Exception $e) {
        $dbNotificationsEnabled = false;
    }
    
    // Check device tokens table (for FCM)
    $deviceTokensEnabled = false;
    $totalDeviceTokens = 0;
    try {
        $result = $db->query("SHOW TABLES LIKE 'device_tokens'");
        if ($result && $result->num_rows > 0) {
            $deviceTokensEnabled = true;
            $countResult = $db->query("SELECT COUNT(*) as count FROM device_tokens WHERE is_active = 1");
            if ($countResult && $countResult->num_rows > 0) {
                $row = $countResult->fetch_assoc();
                $totalDeviceTokens = intval($row['count'] ?? 0);
            }
        }
    } catch (Exception $e) {
        // Table doesn't exist or error
        $deviceTokensEnabled = false;
        $totalDeviceTokens = 0;
    }
    
    // Check notification statistics
    $notificationStats = [
        'total_notifications' => 0,
        'unread_notifications' => 0,
        'notification_types' => []
    ];
    try {
        $result = $db->query("SELECT COUNT(*) as total FROM notifications");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $notificationStats['total_notifications'] = intval($row['total'] ?? 0);
        }
        
        $result = $db->query("SELECT COUNT(*) as total FROM notifications WHERE notif_status = 'unread'");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $notificationStats['unread_notifications'] = intval($row['total'] ?? 0);
        }
        
        $result = $db->query("SELECT notif_type, COUNT(*) as count FROM notifications GROUP BY notif_type");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $notificationStats['notification_types'][$row['notif_type']] = intval($row['count']);
            }
        }
    } catch (Exception $e) {
        // Error getting stats - set defaults
        $notificationStats['total_notifications'] = 0;
        $notificationStats['unread_notifications'] = 0;
        $notificationStats['notification_types'] = [];
    }
    
    // Check if ActivityNotifications class exists (indicates what notification types are implemented)
    $activityNotificationsFile = __DIR__ . '/activity_notifications.php';
    $activityNotificationsEnabled = file_exists($activityNotificationsFile);
    
    // Get actual notification methods being used
    $notificationMethods = [
        'database' => $dbNotificationsEnabled,
        'fcm_push' => $fcmConfigured && $deviceTokensEnabled,
        'email' => false // Email not implemented yet
    ];
    
    // Return actual current settings
    return [
        // Notification Channels (what's actually configured)
        'notification_channels' => [
            'database' => [
                'enabled' => $dbNotificationsEnabled,
                'status' => $dbNotificationsEnabled ? 'Active' : 'Disabled',
                'description' => 'Notifications stored in database (notifications table)',
                'stats' => $notificationStats
            ],
            'fcm_push' => [
                'enabled' => $fcmConfigured && $deviceTokensEnabled,
                'status' => ($fcmConfigured && $deviceTokensEnabled) ? 'Active' : ($fcmConfigured ? 'Configured but no device tokens' : 'Not configured'),
                'description' => 'Firebase Cloud Messaging push notifications',
                'service_account' => $fcmServiceAccountInfo,
                'project_id' => $fcmProjectId,
                'service_account_file' => $fcmServiceAccountPath,
                'device_tokens_count' => $totalDeviceTokens
            ],
            'email' => [
                'enabled' => false,
                'status' => 'Not implemented',
                'description' => 'Email notifications (not implemented yet)'
            ]
        ],
        
        // Notification Types (what's actually implemented)
        'notification_types' => [
            'booking' => [
                'enabled' => true,
                'implemented' => true,
                'methods' => ['Booking Created', 'Booking Approved', 'Booking Declined', 'Booking Cancelled'],
                'description' => 'Notifications for booking activities'
            ],
            'payment' => [
                'enabled' => true,
                'implemented' => true,
                'methods' => ['Payment Received', 'Payment Created', 'Payment Status Updated', 'Payment Overdue'],
                'description' => 'Notifications for payment activities'
            ],
            'maintenance' => [
                'enabled' => true,
                'implemented' => true,
                'methods' => ['Maintenance Request', 'Maintenance Status Updated', 'Maintenance Completed', 'Maintenance Feedback'],
                'description' => 'Notifications for maintenance activities'
            ],
            'announcement' => [
                'enabled' => true,
                'implemented' => true,
                'methods' => ['New Announcement', 'Owner Response'],
                'description' => 'Notifications for announcements'
            ],
            'registration' => [
                'enabled' => true,
                'implemented' => true,
                'methods' => ['Registration Approved', 'Registration Rejected'],
                'description' => 'Notifications for registration activities'
            ],
            'message' => [
                'enabled' => true,
                'implemented' => true,
                'methods' => ['New Message', 'New Group Message'],
                'description' => 'Notifications for messages'
            ],
            'security' => [
                'enabled' => true,
                'implemented' => true,
                'methods' => ['Password Changed', 'Email Changed'],
                'description' => 'Notifications for account security'
            ]
        ],
        
        // Notification System Status
        'system_status' => [
            'activity_notifications_enabled' => $activityNotificationsEnabled,
            'notification_helper_enabled' => file_exists(__DIR__ . '/notification_helper.php'),
            'fcm_config_enabled' => file_exists(__DIR__ . '/fcm_config.php'),
            'total_notification_methods' => count(array_filter($notificationMethods, function($v) { return $v; }))
        ],
        
        // Notification Templates (editable)
        'templates' => $templates,
        
        // Legacy settings (for backward compatibility with UI)
        'email_notifications' => false, // Not implemented
        'push_notifications' => $fcmConfigured && $deviceTokensEnabled,
        'booking_notifications' => true,
        'payment_notifications' => true,
        'maintenance_notifications' => true,
        'announcement_notifications' => true,
        'booking_template' => $templates['booking_created']['template_message'] ?? 'New booking request from {tenant_name} for {room_name}',
        'payment_template' => $templates['payment_received']['template_message'] ?? 'Payment of ₱{amount} has been received',
        'maintenance_template' => $templates['maintenance_request']['template_message'] ?? 'New maintenance request: {description}',
        'announcement_template' => $templates['announcement_new']['template_message'] ?? '{title}: {message}',
        'smtp_server' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_email' => 'admin@boardease.com',
        'fcm_server_key' => $fcmConfigured ? 'Configured (Service Account)' : '',
        'fcm_sender_id' => $fcmProjectId
    ];
}

function getNotificationTemplates($db) {
    $templates = [];
    
    try {
        // Check if table exists - db->query() returns mysqli_result
        $result = $db->query("SHOW TABLES LIKE 'notification_templates'");
        if ($result && $result->num_rows > 0) {
            $stmtResult = $db->query("SELECT template_key, template_title, template_message, notification_type FROM notification_templates ORDER BY notification_type, template_key");
            if ($stmtResult && $stmtResult->num_rows > 0) {
                while ($row = $stmtResult->fetch_assoc()) {
                    $templates[$row['template_key']] = [
                        'title' => $row['template_title'],
                        'message' => $row['template_message'],
                        'type' => $row['notification_type']
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Table doesn't exist or error - return empty array
        error_log("Error getting notification templates: " . $e->getMessage());
    }
    
    // Return default templates if database is empty
    if (empty($templates)) {
        $templates = [
            'booking_created' => ['title' => 'New Booking Request', 'message' => 'You have a new booking request from {tenant_name} for {room_name}', 'type' => 'booking'],
            'booking_approved' => ['title' => 'Booking Approved', 'message' => 'Your booking request for {room_name} has been approved!', 'type' => 'booking'],
            'booking_declined' => ['title' => 'Booking Declined', 'message' => 'Your booking request for {room_name} has been declined.{reason}', 'type' => 'booking'],
            'booking_cancelled' => ['title' => 'Booking Cancelled', 'message' => 'Booking for {room_name} has been cancelled.', 'type' => 'booking'],
            'payment_received' => ['title' => 'Payment Received', 'message' => 'Payment of ₱{amount} has been received{description}', 'type' => 'payment'],
            'payment_created' => ['title' => 'New Payment Pending', 'message' => 'A new payment of ₱{amount} is pending{description}', 'type' => 'payment'],
            'payment_status_updated' => ['title' => 'Payment Status Updated', 'message' => 'Your payment of ₱{amount} status has been updated to: {status}', 'type' => 'payment'],
            'payment_overdue' => ['title' => 'Payment Overdue', 'message' => 'Your payment of ₱{amount} is overdue. Please settle it as soon as possible.', 'type' => 'payment'],
            'maintenance_request' => ['title' => 'New Maintenance Request', 'message' => '{boarder_name} has submitted a maintenance request for {room_name}: {title}', 'type' => 'maintenance'],
            'maintenance_status_updated' => ['title' => 'Maintenance Status Updated', 'message' => 'Maintenance request status updated to: {status}', 'type' => 'maintenance'],
            'maintenance_completed' => ['title' => 'Maintenance Completed', 'message' => 'Your maintenance request has been completed.', 'type' => 'maintenance'],
            'maintenance_feedback' => ['title' => 'Maintenance Feedback', 'message' => 'Feedback received for maintenance request.', 'type' => 'maintenance'],
            'announcement_new' => ['title' => 'New Announcement', 'message' => '{title}: {message}', 'type' => 'announcement'],
            'announcement_owner_response' => ['title' => 'Owner Response', 'message' => 'Owner responded to your review.', 'type' => 'announcement'],
            'registration_approved' => ['title' => 'Registration Approved', 'message' => 'Your registration has been approved! You can now login to your account.', 'type' => 'registration'],
            'registration_rejected' => ['title' => 'Registration Rejected', 'message' => 'Your registration has been rejected. Please contact support for more information.', 'type' => 'registration'],
            'message_new' => ['title' => 'New Message', 'message' => 'New message from {sender_name}: {message_preview}', 'type' => 'message'],
            'message_group' => ['title' => 'New Group Message', 'message' => 'New message in {group_name} from {sender_name}', 'type' => 'message'],
            'security_password_changed' => ['title' => 'Password Changed', 'message' => 'Your password has been successfully changed.', 'type' => 'security'],
            'security_email_changed' => ['title' => 'Email Changed', 'message' => 'Your email address has been successfully changed.', 'type' => 'security']
        ];
    }
    
    return $templates;
}

function saveNotificationSettings($db, $settings) {
    try {
        // Get the actual mysqli connection for escaping
        require_once 'dbConfig.php';
        global $conn;
        
        if (!$conn) {
            throw new Exception("Database connection not available");
        }
        
        // Check if notification_templates table exists, create if not
        $result = $db->query("SHOW TABLES LIKE 'notification_templates'");
        if (!$result || $result->num_rows == 0) {
            // Create table
            $createTable = "
                CREATE TABLE notification_templates (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    template_key VARCHAR(100) UNIQUE NOT NULL,
                    template_title VARCHAR(255) NOT NULL,
                    template_message TEXT NOT NULL,
                    notification_type VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $createResult = $db->query($createTable);
            if (!$createResult) {
                error_log("Error creating notification_templates table: " . $conn->error);
                throw new Exception("Failed to create notification_templates table: " . $conn->error);
            }
        }
        
        // Save templates if provided
        if (isset($settings['templates']) && is_array($settings['templates'])) {
            $savedCount = 0;
            $errorCount = 0;
            
            foreach ($settings['templates'] as $templateKey => $templateData) {
                if (isset($templateData['title']) && isset($templateData['message'])) {
                    // Use mysqli connection for escaping
                    $title = $conn->real_escape_string($templateData['title']);
                    $message = $conn->real_escape_string($templateData['message']);
                    $type = isset($templateData['type']) ? $conn->real_escape_string($templateData['type']) : 'general';
                    $key = $conn->real_escape_string($templateKey);
                    
                    // Use prepared statement approach for safety
                    $stmt = $conn->prepare("
                        INSERT INTO notification_templates (template_key, template_title, template_message, notification_type) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            template_title = ?,
                            template_message = ?,
                            notification_type = ?,
                            updated_at = NOW()
                    ");
                    
                    if ($stmt) {
                        $stmt->bind_param("sssssss", $key, $title, $message, $type, $title, $message, $type);
                        if ($stmt->execute()) {
                            $savedCount++;
                        } else {
                            $errorCount++;
                            error_log("Error saving template $key: " . $stmt->error);
                        }
                        $stmt->close();
                    } else {
                        $errorCount++;
                        error_log("Error preparing statement for template $key: " . $conn->error);
                    }
                }
            }
            
            // Clear template cache so new templates are loaded
            if (class_exists('NotificationTemplateHelper')) {
                NotificationTemplateHelper::clearCache();
            }
            
            if ($errorCount > 0) {
                error_log("Saved $savedCount templates, $errorCount errors");
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving notification settings: " . $e->getMessage());
        throw $e;
    }
}

function sendTestNotification($db, $type, $message) {
    try {
        // Get admin user ID (assuming user_id = 1 is admin)
        $admin_id = 1;
        
        // Create test notification
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status, notif_created_at) 
            VALUES (?, ?, ?, ?, 'unread', NOW())
        ");
        
        $title = "Test Notification - " . ucfirst($type);
        $stmt->execute([$admin_id, $title, $message, $type]);
        
        return [
            'success' => true,
            'message' => 'Test notification sent successfully',
            'details' => [
                'notification_id' => $db->lastInsertId(),
                'type' => $type,
                'message' => $message
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to send test notification: ' . $e->getMessage()
        ];
    }
}
?>
