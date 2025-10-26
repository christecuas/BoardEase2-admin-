<?php
// Simple Admin Users API - Returns users data for admin management
header("Content-Type: application/json");

try {
    require_once 'dbConfig.php';
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get users from the users table with boarding house count
    $sql = "SELECT 
                u.user_id,
                u.reg_id,
                u.status,
                u.profile_picture,
                u.created_at,
                r.first_name,
                r.last_name,
                r.email,
                r.phone,
                r.role,
                CASE 
                    WHEN r.role = 'BH Owner' THEN (
                        SELECT COUNT(*) 
                        FROM boarding_houses bh 
                        WHERE bh.user_id = u.user_id 
                        AND bh.status = 'Active'
                    )
                    ELSE 0
                END as properties_count
            FROM users u
            JOIN registrations r ON u.reg_id = r.id
            ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    $users = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'user_id' => $row['user_id'],
                'reg_id' => $row['reg_id'],
                'full_name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'role' => $row['role'],
                'status' => $row['status'],
                'profile_picture' => $row['profile_picture'],
                'created_at' => $row['created_at'],
                'properties_count' => $row['properties_count']
            ];
        }
    }
    
    $response = [
        'success' => true,
        'data' => [
            'users' => $users,
            'statistics' => [
                'total_users' => count($users),
                'active_users' => count(array_filter($users, function($u) { return $u['status'] === 'active'; })),
                'pending_users' => 0
            ]
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);
$conn->close();
?>



