<?php
// Get Boarding House Details API
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
    
    $bh_id = $_GET['bh_id'] ?? null;
    
    if (!$bh_id) {
        throw new Exception("Boarding house ID is required");
    }
    
    // Get boarding house details
    $sql = "SELECT 
                bh.bh_id,
                bh.bh_name,
                bh.bh_address,
                bh.bh_description,
                bh.status,
                bh.bh_created_at,
                u.user_id,
                u.profile_picture,
                r.first_name,
                r.last_name,
                r.email,
                r.phone,
                r.role
            FROM boarding_houses bh
            JOIN users u ON bh.user_id = u.user_id
            JOIN registrations r ON u.reg_id = r.id
            WHERE bh.bh_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bh_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Boarding house not found");
    }
    
    $boarding_house = $result->fetch_assoc();
    
    // Get room details
    $room_sql = "SELECT 
                    bhr_id,
                    room_category,
                    room_name,
                    price,
                    capacity,
                    room_description,
                    total_rooms,
                    created_at
                FROM boarding_house_rooms 
                WHERE bh_id = ? 
                ORDER BY room_name";
    
    $room_stmt = $conn->prepare($room_sql);
    $room_stmt->bind_param("i", $bh_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();
    
    $rooms = [];
    while ($room = $room_result->fetch_assoc()) {
        $rooms[] = $room;
    }
    
    // Get total statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_rooms,
                    SUM(total_rooms) as total_room_units,
                    AVG(price) as average_rent
                FROM boarding_house_rooms 
                WHERE bh_id = ?";
    
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("i", $bh_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    
    $response = [
        'success' => true,
        'data' => [
            'boarding_house' => $boarding_house,
            'rooms' => $rooms,
            'statistics' => $stats
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
