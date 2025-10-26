<?php
// Get Admin Boarding Houses API - Returns boarding houses data for admin management
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
    
    $status = $_GET['status'] ?? 'all'; // all, active, inactive
    $search = $_GET['search'] ?? '';
    $owner_id = $_GET['owner_id'] ?? null;
    
    // Build base query
    $where_conditions = [];
    $params = [];
    
    // Filter by status
    if ($status === 'active') {
        $where_conditions[] = "bh.status = 'Active'";
    } elseif ($status === 'inactive') {
        $where_conditions[] = "bh.status = 'Inactive'";
    }
    
    // Filter by owner
    if ($owner_id) {
        $where_conditions[] = "bh.user_id = ?";
        $params[] = $owner_id;
    }
    
    // Search functionality
    if (!empty($search)) {
        $where_conditions[] = "(bh.bh_name LIKE ? OR bh.bh_address LIKE ? OR CONCAT(r.f_name, ' ', r.l_name) LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get boarding houses with owner info
    $sql = "
        SELECT 
            bh.bh_id,
            bh.bh_name,
            bh.bh_address,
            bh.bh_description,
            bh.bh_rules,
            bh.number_of_bathroom,
            bh.area,
            bh.build_year,
            bh.status,
            bh.created_at,
            bh.updated_at,
            CONCAT(r.f_name, ' ', r.l_name) as owner_name,
            r.email as owner_email,
            r.phone_number as owner_phone,
            u.user_id as owner_id,
            u.profile_picture as owner_profile_picture,
            (SELECT COUNT(*) FROM boarding_house_rooms bhr WHERE bhr.bh_id = bh.bh_id) as total_rooms,
            (SELECT COUNT(*) FROM room_units ru 
             JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id 
             WHERE bhr.bh_id = bh.bh_id) as total_room_units,
            (SELECT COUNT(*) FROM bookings b 
             JOIN room_units ru ON b.room_id = ru.room_id 
             JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id 
             WHERE bhr.bh_id = bh.bh_id 
             AND b.booking_status IN ('Confirmed', 'Completed')) as total_bookings,
            (SELECT COUNT(*) FROM bookings b 
             JOIN room_units ru ON b.room_id = ru.room_id 
             JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id 
             WHERE bhr.bh_id = bh.bh_id 
             AND b.booking_status = 'Pending') as pending_bookings
        FROM boarding_houses bh
        JOIN users u ON bh.user_id = u.user_id
        JOIN registration r ON u.reg_id = r.reg_id
        {$where_clause}
        ORDER BY bh.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $boarding_houses = $stmt->fetchAll();
    
    // Get statistics
    $stats = [];
    
    // Boarding houses by status
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM boarding_houses
        GROUP BY status
    ");
    $stmt->execute();
    $status_stats = $stmt->fetchAll();
    
    // Total rooms and bookings
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT bh.bh_id) as total_boarding_houses,
            COUNT(DISTINCT bhr.bhr_id) as total_rooms,
            COUNT(DISTINCT ru.room_id) as total_room_units,
            COUNT(DISTINCT b.booking_id) as total_bookings
        FROM boarding_houses bh
        LEFT JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
        LEFT JOIN room_units ru ON bhr.bhr_id = ru.bhr_id
        LEFT JOIN bookings b ON ru.room_id = b.room_id
        WHERE bh.status = 'Active'
    ");
    $stmt->execute();
    $overall_stats = $stmt->fetch();
    
    // Recent boarding houses (last 7 days)
    $stmt = $db->prepare("
        SELECT COUNT(*) as recent_boarding_houses
        FROM boarding_houses
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $recent_boarding_houses = $stmt->fetch()['recent_boarding_houses'];
    
    // Get owners list for filtering
    $stmt = $db->prepare("
        SELECT 
            u.user_id,
            CONCAT(r.f_name, ' ', r.l_name) as owner_name,
            COUNT(bh.bh_id) as boarding_houses_count
        FROM users u
        JOIN registration r ON u.reg_id = r.reg_id
        LEFT JOIN boarding_houses bh ON u.user_id = bh.user_id
        WHERE r.role = 'Owner' AND u.status = 'Active'
        GROUP BY u.user_id, r.f_name, r.l_name
        ORDER BY r.f_name ASC
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll();
    
    $response = [
        'success' => true,
        'data' => [
            'boarding_houses' => $boarding_houses,
            'statistics' => [
                'by_status' => $status_stats,
                'overall' => $overall_stats,
                'recent_boarding_houses' => (int)$recent_boarding_houses
            ],
            'owners' => $owners,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'owner_id' => $owner_id
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




