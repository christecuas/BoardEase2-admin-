<?php
// Get Admin User Statistics API
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'dbConfig.php';

$response = [];

try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get total users count (from registrations table)
    $sql = "SELECT COUNT(*) as total_users 
            FROM registrations r
            JOIN users u ON u.reg_id = r.id
            WHERE u.status = 'Active' AND r.status = 'approved'";
    $result = $conn->query($sql);
    $total_users = $result->fetch_assoc()['total_users'];
    
    // Get total boarders count
    $sql = "SELECT COUNT(*) as total_boarders 
            FROM registrations r
            JOIN users u ON u.reg_id = r.id 
            WHERE u.status = 'Active' AND r.status = 'approved' AND r.role = 'Boarder'";
    $result = $conn->query($sql);
    $total_boarders = $result->fetch_assoc()['total_boarders'];
    
    // Get total owners count
    $sql = "SELECT COUNT(*) as total_owners 
            FROM registrations r
            JOIN users u ON u.reg_id = r.id 
            WHERE u.status = 'Active' AND r.status = 'approved' AND r.role = 'BH Owner'";
    $result = $conn->query($sql);
    $total_owners = $result->fetch_assoc()['total_owners'];
    
    // Get pending registrations count
    $sql = "SELECT COUNT(*) as pending_registrations FROM registrations WHERE status = 'pending'";
    $result = $conn->query($sql);
    $pending_registrations = $result->fetch_assoc()['pending_registrations'];
    
    // Get users by status
    $sql = "SELECT 
                r.role,
                u.status,
                COUNT(*) as count
            FROM users u
            JOIN registrations r ON u.reg_id = r.id
            GROUP BY r.role, u.status";
    $result = $conn->query($sql);
    $users_by_status = [];
    while ($row = $result->fetch_assoc()) {
        $users_by_status[] = $row;
    }
    
    // Get recent users (last 30 days)
    $sql = "SELECT COUNT(*) as recent_users 
            FROM users u
            JOIN registrations r ON u.reg_id = r.id
            WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $result = $conn->query($sql);
    $recent_users = $result->fetch_assoc()['recent_users'];
    
    $response = [
        'success' => true,
        'data' => [
            'total_users' => (int)$total_users,
            'total_boarders' => (int)$total_boarders,
            'total_owners' => (int)$total_owners,
            'pending_registrations' => (int)$pending_registrations,
            'recent_users' => (int)$recent_users,
            'users_by_status' => $users_by_status
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
