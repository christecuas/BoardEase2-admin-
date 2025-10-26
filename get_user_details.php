<?php
// Get User Details API
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
    
    // Get user ID from request
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($user_id <= 0) {
        throw new Exception("Invalid user ID");
    }
    
    // Get user details with registration info
    $sql = "SELECT 
                u.user_id,
                u.reg_id,
                u.profile_picture,
                u.status as user_status,
                r.first_name,
                r.middle_name,
                r.last_name,
                r.email,
                r.phone,
                r.role,
                r.status as reg_status,
                r.created_at as reg_created_at,
                r.updated_at as reg_updated_at,
                r.address,
                r.birth_date,
                r.gcash_num,
                r.valid_id_type,
                r.id_number,
                r.cb_agreed,
                r.idFrontFile,
                r.idBackFile,
                r.gcash_qr
            FROM users u
            JOIN registrations r ON u.reg_id = r.id
            WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $user = $result->fetch_assoc();
    
    // Get boarding houses if user is an owner
    $boarding_houses = [];
    if ($user['role'] === 'BH Owner') {
        $sql = "SELECT 
                    bh.bh_id,
                    bh.bh_name,
                    bh.bh_address,
                    bh.bh_description,
                    bh.status,
                    bh.bh_created_at,
                    (SELECT COUNT(*) FROM boarding_house_rooms bhr WHERE bhr.bh_id = bh.bh_id) as total_rooms
                FROM boarding_houses bh
                WHERE bh.user_id = ?
                ORDER BY bh.bh_created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $boarding_houses[] = $row;
        }
    }
    
    // Get bookings if user is a boarder
    $bookings = [];
    if ($user['role'] === 'Boarder') {
        $sql = "SELECT 
                    b.booking_id,
                    b.booking_date,
                    b.check_in_date,
                    b.check_out_date,
                    b.status,
                    b.total_amount,
                    b.created_at,
                    bh.bh_name,
                    bh.bh_address
                FROM bookings b
                JOIN boarding_house bh ON b.bh_id = bh.bh_id
                WHERE b.user_id = ?
                ORDER BY b.created_at DESC
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
    
    $response = [
        'success' => true,
        'data' => [
            'user' => $user,
            'boarding_houses' => $boarding_houses,
            'bookings' => $bookings
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
