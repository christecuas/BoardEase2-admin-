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

$response = [];

try {
    $db = getDB();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $action = $_GET['action'] ?? 'list'; // list, send, stats
    $type = $_GET['type'] ?? 'all'; // all, system, user
    $status = $_GET['status'] ?? 'all'; // all, unread, read
    
    if ($action === 'send') {
        // Handle sending notifications
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        $recipients = $input['recipients'] ?? 'all'; // all, boarders, owners, specific users
        $type = $input['notification_type'] ?? 'announcement';
        
        if (empty($title) || empty($message)) {
            throw new Exception("Title and message are required");
        }
        
        $sent_count = 0;
        
        if ($recipients === 'all') {
            // Send to all active users
            $stmt = $db->prepare("
                SELECT user_id FROM users WHERE status = 'Active'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status, notif_created_at)
                    VALUES (?, ?, ?, ?, 'unread', NOW())
                ");
                $stmt->execute([$user['user_id'], $title, $message, $type]);
                $sent_count++;
            }
        } elseif ($recipients === 'boarders') {
            // Send to all boarders
            $stmt = $db->prepare("
                SELECT u.user_id FROM users u
                JOIN registration r ON u.reg_id = r.reg_id
                WHERE u.status = 'Active' AND r.role = 'Boarder'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status, notif_created_at)
                    VALUES (?, ?, ?, ?, 'unread', NOW())
                ");
                $stmt->execute([$user['user_id'], $title, $message, $type]);
                $sent_count++;
            }
        } elseif ($recipients === 'owners') {
            // Send to all owners
            $stmt = $db->prepare("
                SELECT u.user_id FROM users u
                JOIN registration r ON u.reg_id = r.reg_id
                WHERE u.status = 'Active' AND r.role = 'Owner'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status, notif_created_at)
                    VALUES (?, ?, ?, ?, 'unread', NOW())
                ");
                $stmt->execute([$user['user_id'], $title, $message, $type]);
                $sent_count++;
            }
        }
        
        $response = [
            'success' => true,
            'message' => "Notification sent to {$sent_count} users",
            'data' => [
                'sent_count' => $sent_count
            ]
        ];
        
    } else {
        // List notifications
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
                CONCAT(r.f_name, ' ', r.l_name) as user_name,
                r.email as user_email,
                r.role as user_role
            FROM notifications n
            JOIN users u ON n.user_id = u.user_id
            JOIN registration r ON u.reg_id = r.reg_id
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
        
        $response = [
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'statistics' => [
                    'by_type' => $type_stats,
                    'by_status' => $status_stats,
                    'recent_notifications' => (int)$recent_notifications,
                    'unread_count' => (int)$unread_count
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
