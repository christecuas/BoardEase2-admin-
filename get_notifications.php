<?php
// Get notifications for a user
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once 'db_helper.php';
header('Content-Type: application/json');

try {
    $user_id = $_GET['user_id'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    $notif_type = $_GET['notif_type'] ?? null; // Filter by type
    
    if (!$user_id) {
        throw new Exception('Missing required parameter: user_id');
    }
    
    $db = getDB();
    
    // Validate user_id to prevent SQL injection and ensure security
    if (!is_numeric($user_id)) {
        throw new Exception('Invalid user_id parameter');
    }
    
    // Build query with optional type filter
    // IMPORTANT: This query ONLY returns notifications for the specific user_id
    // Each user can ONLY see their own notifications, not notifications for other users
    $where_clause = "WHERE user_id = ?";
    $params = [$user_id];
    
    if ($notif_type) {
        $where_clause .= " AND notif_type = ?";
        $params[] = $notif_type;
    }
    
    // Get notifications - build complete query
    // SECURITY: The WHERE clause ensures users can only see their own notifications
    $query = "
        SELECT notif_id, user_id, notif_title, notif_message, notif_type, 
               notif_status, notif_created_at
        FROM notifications 
        $where_clause
        ORDER BY notif_created_at DESC 
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
    
    // Verify all notifications belong to the requested user (extra security check)
    foreach ($notifications as $notif) {
        if ($notif['user_id'] != $user_id) {
            error_log("SECURITY WARNING: Notification " . $notif['notif_id'] . " does not belong to user $user_id!");
        }
    }
    
    // Get total count
    $count_query = "
        SELECT COUNT(*) as total_count 
        FROM notifications 
        $where_clause
    ";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch()['total_count'];
    
    // Get unread count - ONLY for this specific user
    // SECURITY: The WHERE clause ensures users can only see their own unread count
    $unread_stmt = $db->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND notif_status = 'unread'
    ");
    $unread_stmt->execute([$user_id]);
    $unread_result = $unread_stmt->fetch();
    $unread_count = $unread_result ? (int)$unread_result['unread_count'] : 0;
    
    $response = [
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'total_count' => (int)$total_count,
            'unread_count' => (int)$unread_count,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => null
    ];
}

ob_clean();
echo json_encode($response);
exit;
?>
