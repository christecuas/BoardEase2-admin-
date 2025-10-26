<?php
// Get Admin Users API - Returns users data for admin management
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
    
    $user_type = $_GET['type'] ?? 'all'; // all, boarders, owners
    $status = $_GET['status'] ?? 'all'; // all, active, inactive
    $search = $_GET['search'] ?? '';
    
    // Build base query
    $where_conditions = [];
    $params = [];
    
    // Filter by user type
    if ($user_type === 'boarders') {
        $where_conditions[] = "r.role = 'Boarder'";
    } elseif ($user_type === 'owners') {
        $where_conditions[] = "r.role = 'Owner'";
    }
    
    // Filter by status
    if ($status === 'active') {
        $where_conditions[] = "u.status = 'Active'";
    } elseif ($status === 'inactive') {
        $where_conditions[] = "u.status = 'Inactive'";
    }
    
    // Search functionality
    if (!empty($search)) {
        $where_conditions[] = "(r.f_name LIKE ? OR r.l_name LIKE ? OR r.email LIKE ? OR CONCAT(r.f_name, ' ', r.l_name) LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get users with additional info
    $sql = "
        SELECT 
            u.user_id,
            CONCAT(r.f_name, ' ', r.l_name) as full_name,
            r.f_name as first_name,
            r.l_name as last_name,
            r.email,
            r.phone_number,
            r.role,
            u.status,
            u.profile_picture,
            u.created_at,
            u.updated_at,
            CASE 
                WHEN r.role = 'Boarder' THEN (
                    SELECT COUNT(*) 
                    FROM bookings b 
                    WHERE b.user_id = u.user_id 
                    AND b.booking_status IN ('Confirmed', 'Completed')
                )
                WHEN r.role = 'Owner' THEN (
                    SELECT COUNT(*) 
                    FROM boarding_houses bh 
                    WHERE bh.user_id = u.user_id 
                    AND bh.status = 'Active'
                )
                ELSE 0
            END as activity_count
        FROM users u
        JOIN registration r ON u.reg_id = r.reg_id
        {$where_clause}
        ORDER BY u.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get statistics
    $stats = [];
    
    // Total users by role
    $stmt = $db->prepare("
        SELECT 
            r.role,
            COUNT(*) as count
        FROM users u
        JOIN registration r ON u.reg_id = r.reg_id
        WHERE u.status = 'Active'
        GROUP BY r.role
    ");
    $stmt->execute();
    $role_stats = $stmt->fetchAll();
    
    // Users by status
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM users
        GROUP BY status
    ");
    $stmt->execute();
    $status_stats = $stmt->fetchAll();
    
    // Recent registrations (last 7 days)
    $stmt = $db->prepare("
        SELECT COUNT(*) as recent_registrations
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $recent_registrations = $stmt->fetch()['recent_registrations'];
    
    $response = [
        'success' => true,
        'data' => [
            'users' => $users,
            'statistics' => [
                'by_role' => $role_stats,
                'by_status' => $status_stats,
                'recent_registrations' => (int)$recent_registrations
            ],
            'filters' => [
                'type' => $user_type,
                'status' => $status,
                'search' => $search
            ]
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
