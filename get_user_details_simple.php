<?php
// Simple User Details API for testing
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
    
    // Simple query to get user details
    $sql = "SELECT 
                u.user_id,
                u.reg_id,
                u.profile_picture,
                u.status as user_status,
                r.first_name,
                r.middle_name,
                r.last_name,
                r.suffix,
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
    // Get business permits if user is an owner
    $business_permits = [];
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
        
        // Get business permits for this registration
        $permit_sql = "SELECT 
                    permit_id,
                    permit_file,
                    permit_number,
                    created_at,
                    updated_at
                FROM bs_permits
                WHERE reg_id = ?
                ORDER BY permit_number ASC";
        
        $permit_stmt = $conn->prepare($permit_sql);
        $permit_stmt->bind_param("i", $user['reg_id']);
        $permit_stmt->execute();
        $permit_result = $permit_stmt->get_result();
        
        while ($permit_row = $permit_result->fetch_assoc()) {
            $business_permits[] = $permit_row;
        }
        $permit_stmt->close();
    }
    
    // Get bookings if user is a boarder
    $bookings = [];
    if ($user['role'] === 'Boarder') {
        $sql = "SELECT 
                    b.booking_id,
                    b.room_id,
                    b.booking_date,
                    b.start_date as check_in_date,
                    b.end_date as check_out_date,
                    b.booking_status as status,
                    bhr.price as total_amount,
                    b.booking_date as created_at,
                    bh.bh_id,
                    bh.bh_name,
                    bh.bh_address,
                    ru.room_id as room_unit_id,
                    ru.room_number,
                    bhr.bhr_id,
                    bhr.room_name,
                    bhr.room_category,
                    bhr.capacity,
                    bhr.room_description
                FROM bookings b
                JOIN room_units ru ON b.room_id = ru.room_id
                JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE b.user_id = ?
                ORDER BY 
                    CASE 
                        WHEN b.booking_status = 'Pending' THEN 1
                        WHEN b.booking_status = 'Confirmed' THEN 2
                        WHEN b.booking_status = 'Completed' THEN 3
                        WHEN b.booking_status = 'Cancelled' THEN 4
                        ELSE 5
                    END,
                    b.booking_date DESC
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
            'bookings' => $bookings,
            'business_permits' => $business_permits
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
