<?php
// Simple version of the API using direct MySQLi
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
    
    $status = $_GET['status'] ?? 'all';
    
    // Build query
    $where_clause = '';
    if ($status === 'active') {
        $where_clause = "WHERE bh.status = 'Active'";
    } elseif ($status === 'inactive') {
        $where_clause = "WHERE bh.status = 'Inactive'";
    }
    
    // Get boarding houses with owner info
    $sql = "
        SELECT 
            bh.bh_id,
            bh.bh_name,
            bh.bh_address,
            bh.bh_description,
            bh.status,
            bh.bh_created_at,
            COALESCE(CONCAT(r.first_name, ' ', r.last_name), 'Unknown Owner') as owner_name,
            COALESCE(r.email, 'No email') as owner_email,
            COALESCE(r.phone, 'No phone') as owner_phone,
            u.user_id as owner_id,
            (SELECT COUNT(*) FROM boarding_house_rooms bhr WHERE bhr.bh_id = bh.bh_id) as total_rooms
        FROM boarding_houses bh
        LEFT JOIN users u ON bh.user_id = u.user_id
        LEFT JOIN registrations r ON u.reg_id = r.id
        {$where_clause}
        ORDER BY bh.bh_created_at DESC
    ";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $boarding_houses = [];
        while ($row = $result->fetch_assoc()) {
            $boarding_houses[] = $row;
        }
        
        $response = [
            'success' => true,
            'data' => [
                'boarding_houses' => $boarding_houses
            ]
        ];
    } else {
        throw new Exception("Query failed: " . $conn->error);
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
