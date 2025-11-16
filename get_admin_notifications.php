<?php
// Get Admin Notifications API - Returns notifications data for admin management
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'db_helper.php';
require_once 'admin_system_notifications.php';
require_once 'activity_notifications.php';

$response = [];

try {
    $db = getDB();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $action = $_GET['action'] ?? 'list'; // list, send, system, stats
    $type = $_GET['type'] ?? 'all'; // all, system, user
    $status = $_GET['status'] ?? 'all'; // all, unread, read
    
    if ($action === 'send') {
        // Handle sending notifications using ActivityNotifications
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        $recipients = $input['recipients'] ?? 'all'; // all, boarders, owners, specific users
        $notif_type = $input['notification_type'] ?? 'announcement';
        
        if (empty($title) || empty($message)) {
            throw new Exception("Title and message are required");
        }
        
        $sent_count = 0;
        $failed_count = 0;
        
        // Get target users based on recipients
        if ($recipients === 'all') {
            // Send to all active users
            $stmt = $db->prepare("
                SELECT u.user_id FROM users u
                JOIN registrations r ON u.reg_id = r.id
                WHERE u.status = 'Active' AND r.status = 'approved'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
        } elseif ($recipients === 'boarders') {
            // Send to all boarders
            $stmt = $db->prepare("
                SELECT u.user_id FROM users u
                JOIN registrations r ON u.reg_id = r.id
                WHERE u.status = 'Active' AND r.status = 'approved' AND r.role = 'Boarder'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
        } elseif ($recipients === 'owners') {
            // Send to all owners - check for both 'BH Owner' and 'Owner' roles
            $stmt = $db->prepare("
                SELECT u.user_id FROM users u
                JOIN registrations r ON u.reg_id = r.id
                WHERE u.status = 'Active' AND r.status = 'approved' 
                AND (r.role = 'BH Owner' OR r.role = 'Owner')
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
        } else {
            $users = [];
        }
        
        if (empty($users)) {
            $response = [
                'success' => false,
                'message' => 'No users found for the selected recipient type',
                'data' => [
                    'sent_count' => 0,
                    'failed_count' => 0,
                    'total_users' => 0
                ]
            ];
            echo json_encode($response, JSON_PRETTY_PRINT);
            exit;
        }
        
        // Send notifications to all users using ActivityNotifications
        // IMPORTANT: Loop through each user and send individually - this ensures each user
        // only receives their own notification, not notifications meant for others
        foreach ($users as $user) {
            try {
                // Validate user_id
                if (empty($user['user_id']) || !is_numeric($user['user_id'])) {
                    error_log("Invalid user_id in recipients list: " . var_export($user, true));
                    $failed_count++;
                    continue;
                }
                
                // Use the notification type from the form
                // This sends to ONE user at a time - each user gets their own notification
                $result = ActivityNotifications::notifyNewAnnouncement($user['user_id'], [
                    'title' => $title,
                    'content' => $message,
                    'type' => $notif_type
                ]);
                
                if ($result['success'] ?? false) {
                    $sent_count++;
                    // Log for debugging (limit to first few to avoid spam)
                    if ($sent_count <= 3) {
                        error_log("Announcement sent to user_id=" . $user['user_id'] . " (this is correct - announcements go to all users)");
                    }
                } else {
                    $failed_count++;
                    error_log("Failed to send notification to user {$user['user_id']}: " . ($result['message'] ?? 'Unknown error'));
                }
            } catch (Exception $e) {
                error_log("Error sending notification to user {$user['user_id']}: " . $e->getMessage());
                $failed_count++;
            }
        }
        
        // Log summary
        error_log("Admin announcement sent: Title='$title', Recipients='$recipients', Total users=" . count($users) . ", Sent=$sent_count, Failed=$failed_count");
        
        // Log the notification sending activity
        error_log("Admin notification sent: Title='$title', Recipients='$recipients', Sent=$sent_count, Failed=$failed_count, Total=" . count($users));
        
        $response = [
            'success' => true,
            'message' => "Notification sent to {$sent_count} users" . ($failed_count > 0 ? " ({$failed_count} failed)" : ""),
            'data' => [
                'sent_count' => $sent_count,
                'failed_count' => $failed_count,
                'total_users' => count($users)
            ]
        ];
        
    } elseif ($action === 'system') {
        // Get system notifications for admin dashboard
        // Default to 1000 to show all notifications (or specify higher limit)
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // If limit is 0 or negative, set to 1000 to show all
        if ($limit <= 0) {
            $limit = 1000;
        }
        
        $systemNotifications = AdminSystemNotifications::getSystemNotifications($limit, $offset);
        
        $response = [
            'success' => true,
            'data' => [
                'system_notifications' => $systemNotifications['data'] ?? [],
                'total' => $systemNotifications['total'] ?? 0
            ]
        ];
        
    } else {
        // List user notifications
        $where_conditions = [];
        $params = [];
        
        // Filter by type
        if ($type === 'system') {
            $where_conditions[] = "n.notif_type IN ('announcement', 'maintenance', 'general')";
        } elseif ($type === 'user') {
            $where_conditions[] = "n.notif_type IN ('booking', 'payment')";
        }
        
        // Filter by status
        if ($status === 'unread') {
            $where_conditions[] = "n.notif_status = 'unread'";
        } elseif ($status === 'read') {
            $where_conditions[] = "n.notif_status = 'read'";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get notifications with user info
        $sql = "
            SELECT 
                n.notif_id,
                n.user_id,
                n.notif_title,
                n.notif_message,
                n.notif_type,
                n.notif_status,
                n.notif_created_at,
                CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as user_name,
                r.email as user_email,
                r.role as user_role
            FROM notifications n
            JOIN users u ON n.user_id = u.user_id
            JOIN registrations r ON u.reg_id = r.id
            {$where_clause}
            ORDER BY n.notif_created_at DESC
            LIMIT 100
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll();
        
        // Get notification statistics
        $stats = [];
        
        // Notifications by type
        $stmt = $db->prepare("
            SELECT 
                notif_type,
                COUNT(*) as count
            FROM notifications
            GROUP BY notif_type
        ");
        $stmt->execute();
        $type_stats = $stmt->fetchAll();
        
        // Notifications by status
        $stmt = $db->prepare("
            SELECT 
                notif_status,
                COUNT(*) as count
            FROM notifications
            GROUP BY notif_status
        ");
        $stmt->execute();
        $status_stats = $stmt->fetchAll();
        
        // Recent notifications (last 7 days)
        $stmt = $db->prepare("
            SELECT COUNT(*) as recent_notifications
            FROM notifications
            WHERE notif_created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $recent_notifications = $stmt->fetch()['recent_notifications'];
        
        // Unread notifications count
        $stmt = $db->prepare("
            SELECT COUNT(*) as unread_count
            FROM notifications
            WHERE notif_status = 'unread'
        ");
        $stmt->execute();
        $unread_count = $stmt->fetch()['unread_count'];
        
        // Get system notifications count
        $systemNotifications = AdminSystemNotifications::getSystemNotifications(100, 0);
        $system_count = $systemNotifications['total'] ?? 0;
        
        $response = [
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'system_notifications' => $systemNotifications['data'] ?? [],
                'statistics' => [
                    'by_type' => $type_stats,
                    'by_status' => $status_stats,
                    'recent_notifications' => (int)$recent_notifications,
                    'unread_count' => (int)$unread_count,
                    'system_notifications_count' => $system_count
                ],
                'filters' => [
                    'type' => $type,
                    'status' => $status
                ]
            ]
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
