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
    // For now, return default settings
    // In a real implementation, you would store these in a database table
    return [
        'email_notifications' => true,
        'push_notifications' => true,
        'booking_notifications' => true,
        'payment_notifications' => true,
        'maintenance_notifications' => true,
        'announcement_notifications' => true,
        'booking_template' => 'New booking request from {{user_name}} for {{room_name}} at {{boarding_house_name}}. Please review and approve.',
        'payment_template' => 'Payment of ₱{{amount}} received from {{user_name}} for {{room_name}}. Payment method: {{payment_method}}.',
        'maintenance_template' => 'Maintenance request from {{user_name}}: {{description}}. Status: {{status}}.',
        'announcement_template' => '{{title}}: {{message}}',
        'smtp_server' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_email' => 'admin@boardease.com',
        'fcm_server_key' => '',
        'fcm_sender_id' => ''
    ];
}

function saveNotificationSettings($db, $settings) {
    // For now, just return true
    // In a real implementation, you would save these to a database table
    // You could create a table like:
    // CREATE TABLE notification_settings (
    //     id INT PRIMARY KEY AUTO_INCREMENT,
    //     setting_key VARCHAR(100) UNIQUE,
    //     setting_value TEXT,
    //     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    // );
    
    // Example implementation:
    /*
    $db->beginTransaction();
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("
                INSERT INTO notification_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, is_bool($value) ? ($value ? '1' : '0') : $value]);
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    */
    
    return true;
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
