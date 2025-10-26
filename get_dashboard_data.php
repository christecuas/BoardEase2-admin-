<?php
// Get Dashboard Data API - Returns comprehensive dashboard statistics
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
    
    // Get total users count
    $stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users WHERE status = 'Active'");
    $stmt->execute();
    $total_users = $stmt->fetch()['total_users'];
    
    // Get total boarders count
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT b.user_id) as total_boarders 
        FROM bookings b 
        JOIN users u ON b.user_id = u.user_id 
        WHERE u.status = 'Active' AND b.booking_status IN ('Confirmed', 'Completed')
    ");
    $stmt->execute();
    $total_boarders = $stmt->fetch()['total_boarders'];
    
    // Get total owners count
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT bh.user_id) as total_owners 
        FROM boarding_houses bh 
        JOIN users u ON bh.user_id = u.user_id 
        WHERE u.status = 'Active' AND bh.status = 'Active'
    ");
    $stmt->execute();
    $total_owners = $stmt->fetch()['total_owners'];
    
    // Get total boarding houses count
    $stmt = $db->prepare("SELECT COUNT(*) as total_boarding_houses FROM boarding_houses WHERE status = 'Active'");
    $stmt->execute();
    $total_boarding_houses = $stmt->fetch()['total_boarding_houses'];
    
    // Get total bookings count
    $stmt = $db->prepare("SELECT COUNT(*) as total_bookings FROM bookings");
    $stmt->execute();
    $total_bookings = $stmt->fetch()['total_bookings'];
    
    // Get pending bookings count
    $stmt = $db->prepare("SELECT COUNT(*) as pending_bookings FROM bookings WHERE booking_status = 'Pending'");
    $stmt->execute();
    $pending_bookings = $stmt->fetch()['pending_bookings'];
    
    // Get confirmed bookings count
    $stmt = $db->prepare("SELECT COUNT(*) as confirmed_bookings FROM bookings WHERE booking_status = 'Confirmed'");
    $stmt->execute();
    $confirmed_bookings = $stmt->fetch()['confirmed_bookings'];
    
    // Get user growth data (last 6 months)
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_users
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $user_growth = $stmt->fetchAll();
    
    // Get booking trends (last 6 months)
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(booking_date, '%Y-%m') as month,
            COUNT(*) as bookings
        FROM bookings 
        WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $booking_trends = $stmt->fetchAll();
    
    // Get recent users (last 10)
    $stmt = $db->prepare("
        SELECT 
            u.user_id,
            CONCAT(r.f_name, ' ', r.l_name) as full_name,
            r.email,
            r.role,
            u.status,
            u.created_at,
            u.profile_picture
        FROM users u
        JOIN registration r ON u.reg_id = r.reg_id
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_users = $stmt->fetchAll();
    
    // Get recent bookings (last 10)
    $stmt = $db->prepare("
        SELECT 
            b.booking_id,
            CONCAT(r.f_name, ' ', r.l_name) as boarder_name,
            bh.bh_name as boarding_house_name,
            b.booking_status,
            b.booking_date,
            b.start_date,
            b.end_date
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN registration r ON u.reg_id = r.reg_id
        JOIN room_units ru ON b.room_id = ru.room_id
        JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        ORDER BY b.booking_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_bookings = $stmt->fetchAll();
    
    // Get boarding houses by status
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM boarding_houses 
        GROUP BY status
    ");
    $stmt->execute();
    $boarding_houses_by_status = $stmt->fetchAll();
    
    // Get users by role
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
    $users_by_role = $stmt->fetchAll();
    
    $response = [
        'success' => true,
        'data' => [
            'overview' => [
                'total_users' => (int)$total_users,
                'total_boarders' => (int)$total_boarders,
                'total_owners' => (int)$total_owners,
                'total_boarding_houses' => (int)$total_boarding_houses,
                'total_bookings' => (int)$total_bookings,
                'pending_bookings' => (int)$pending_bookings,
                'confirmed_bookings' => (int)$confirmed_bookings
            ],
            'charts' => [
                'user_growth' => $user_growth,
                'booking_trends' => $booking_trends,
                'boarding_houses_by_status' => $boarding_houses_by_status,
                'users_by_role' => $users_by_role
            ],
            'recent_activity' => [
                'recent_users' => $recent_users,
                'recent_bookings' => $recent_bookings
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
