<?php
// Final Analytics Data API - Works with actual database structure
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
    
    // Get period filter (default to last 30 days)
    $period = isset($_GET['period']) ? $_GET['period'] : '30';
    $days = 30;
    
    switch($period) {
        case '7':
            $days = 7;
            break;
        case '30':
            $days = 30;
            break;
        case '90':
            $days = 90;
            break;
        case '365':
            $days = 365;
            break;
    }
    
    // 1. USER ANALYTICS
    // Total users
    $stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users WHERE status = 'Active'");
    $stmt->execute();
    $total_users = $stmt->fetch()['total_users'];
    
    // New users this period (using registrations table)
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_users 
        FROM registrations 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
    ");
    $stmt->execute();
    $new_users = $stmt->fetch()['new_users'];
    
    // Users by role
    $stmt = $db->prepare("
        SELECT r.role, COUNT(*) as count 
        FROM users u 
        JOIN registrations r ON u.reg_id = r.id 
        WHERE u.status = 'Active' 
        GROUP BY r.role
    ");
    $stmt->execute();
    $users_by_role = $stmt->fetchAll();
    
    // User growth over time (last 30 days)
    $stmt = $db->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM registrations 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at) 
        ORDER BY date
    ");
    $stmt->execute();
    $user_growth = $stmt->fetchAll();
    
    // 2. PROPERTY ANALYTICS
    // Total properties
    $stmt = $db->prepare("SELECT COUNT(*) as total_properties FROM boarding_houses WHERE status = 'Active'");
    $stmt->execute();
    $total_properties = $stmt->fetch()['total_properties'];
    
    // Properties by status
    $stmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM boarding_houses 
        GROUP BY status
    ");
    $stmt->execute();
    $properties_by_status = $stmt->fetchAll();
    
    // New properties this period
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_properties 
        FROM boarding_houses 
        WHERE status = 'Active' AND DATE(bh_created_at) >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
    ");
    $stmt->execute();
    $new_properties = $stmt->fetch()['new_properties'];
    
    // 3. BOOKING ANALYTICS
    // Total bookings
    $stmt = $db->prepare("SELECT COUNT(*) as total_bookings FROM bookings");
    $stmt->execute();
    $total_bookings = $stmt->fetch()['total_bookings'];
    
    // Bookings by status
    $stmt = $db->prepare("
        SELECT booking_status, COUNT(*) as count 
        FROM bookings 
        GROUP BY booking_status
    ");
    $stmt->execute();
    $bookings_by_status = $stmt->fetchAll();
    
    // New bookings this period
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_bookings 
        FROM bookings 
        WHERE DATE(booking_date) >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
    ");
    $stmt->execute();
    $new_bookings = $stmt->fetch()['new_bookings'];
    
    // Booking trends over time
    $stmt = $db->prepare("
        SELECT DATE(booking_date) as date, COUNT(*) as count 
        FROM bookings 
        WHERE DATE(booking_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(booking_date) 
        ORDER BY date
    ");
    $stmt->execute();
    $booking_trends = $stmt->fetchAll();
    
    // 4. REVENUE ANALYTICS (using payments table)
    // Total revenue
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(payment_amount), 0) as total_revenue 
        FROM payments 
        WHERE payment_status = 'Completed'
    ");
    $stmt->execute();
    $total_revenue = $stmt->fetch()['total_revenue'];
    
    // Revenue this period
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(payment_amount), 0) as period_revenue 
        FROM payments 
        WHERE payment_status = 'Completed' AND DATE(payment_date) >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
    ");
    $stmt->execute();
    $period_revenue = $stmt->fetch()['period_revenue'];
    
    // Revenue trends over time
    $stmt = $db->prepare("
        SELECT DATE(payment_date) as date, COALESCE(SUM(payment_amount), 0) as revenue 
        FROM payments 
        WHERE payment_status = 'Completed' AND DATE(payment_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(payment_date) 
        ORDER BY date
    ");
    $stmt->execute();
    $revenue_trends = $stmt->fetchAll();
    
    // 5. GEOGRAPHIC DISTRIBUTION
    $stmt = $db->prepare("
        SELECT 
            CASE 
                WHEN bh_address LIKE '%Quezon City%' OR bh_address LIKE '%QC%' THEN 'Quezon City'
                WHEN bh_address LIKE '%Makati%' THEN 'Makati City'
                WHEN bh_address LIKE '%Manila%' THEN 'Manila City'
                WHEN bh_address LIKE '%Pasig%' THEN 'Pasig City'
                WHEN bh_address LIKE '%Taguig%' THEN 'Taguig City'
                WHEN bh_address LIKE '%Mandaluyong%' THEN 'Mandaluyong City'
                WHEN bh_address LIKE '%Marikina%' THEN 'Marikina City'
                WHEN bh_address LIKE '%Muntinlupa%' THEN 'Muntinlupa City'
                WHEN bh_address LIKE '%Las Piñas%' THEN 'Las Piñas City'
                WHEN bh_address LIKE '%Parañaque%' THEN 'Parañaque City'
                WHEN bh_address LIKE '%Valenzuela%' THEN 'Valenzuela City'
                WHEN bh_address LIKE '%Malabon%' THEN 'Malabon City'
                WHEN bh_address LIKE '%Navotas%' THEN 'Navotas City'
                WHEN bh_address LIKE '%Caloocan%' THEN 'Caloocan City'
                WHEN bh_address LIKE '%San Juan%' THEN 'San Juan City'
                WHEN bh_address LIKE '%Pateros%' THEN 'Pateros'
                WHEN bh_address LIKE '%Cebu%' THEN 'Cebu City'
                WHEN bh_address LIKE '%Bohol%' THEN 'Bohol'
                WHEN bh_address LIKE '%Calape%' THEN 'Calape, Bohol'
                ELSE 'Other Areas'
            END as location,
            COUNT(*) as count
        FROM boarding_houses 
        WHERE status = 'Active'
        GROUP BY location
        ORDER BY count DESC
    ");
    $stmt->execute();
    $geographic_distribution = $stmt->fetchAll();
    
    // 6. OCCUPANCY RATES
    // Calculate occupancy rate (active boarders vs available rooms)
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT ab.active_id) as occupied_rooms,
            (SELECT COUNT(*) FROM room_units WHERE status = 'Available') as available_rooms,
            (SELECT COUNT(*) FROM room_units) as total_rooms
        FROM active_boarders ab 
        WHERE ab.status = 'Active'
    ");
    $stmt->execute();
    $occupancy_data = $stmt->fetch();
    $occupancy_rate = $occupancy_data['total_rooms'] > 0 ? 
        round(($occupancy_data['occupied_rooms'] / $occupancy_data['total_rooms']) * 100, 1) : 0;
    
    // 7. PAYMENT ANALYTICS
    // Payment success rate
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_payments,
            SUM(CASE WHEN payment_status = 'Completed' THEN 1 ELSE 0 END) as successful_payments
        FROM payments
    ");
    $stmt->execute();
    $payment_data = $stmt->fetch();
    $success_rate = $payment_data['total_payments'] > 0 ? 
        round(($payment_data['successful_payments'] / $payment_data['total_payments']) * 100, 1) : 0;
    
    // 8. ROOM ANALYTICS
    // Total rooms and room types
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT bhr.bhr_id) as total_room_types,
            SUM(bhr.total_rooms) as total_room_units,
            AVG(bhr.price) as average_rent
        FROM boarding_house_rooms bhr
        JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        WHERE bh.status = 'Active'
    ");
    $stmt->execute();
    $room_stats = $stmt->fetch();
    
    $response = [
        'success' => true,
        'data' => [
            'period' => $period,
            'user_analytics' => [
                'total_users' => (int)$total_users,
                'new_users' => (int)$new_users,
                'users_by_role' => $users_by_role,
                'growth_trend' => $user_growth
            ],
            'property_analytics' => [
                'total_properties' => (int)$total_properties,
                'new_properties' => (int)$new_properties,
                'properties_by_status' => $properties_by_status,
                'occupancy_rate' => $occupancy_rate
            ],
            'booking_analytics' => [
                'total_bookings' => (int)$total_bookings,
                'new_bookings' => (int)$new_bookings,
                'bookings_by_status' => $bookings_by_status,
                'booking_trends' => $booking_trends
            ],
            'revenue_analytics' => [
                'total_revenue' => (float)$total_revenue,
                'period_revenue' => (float)$period_revenue,
                'revenue_trends' => $revenue_trends
            ],
            'geographic_distribution' => $geographic_distribution,
            'payment_analytics' => [
                'success_rate' => $success_rate,
                'total_payments' => (int)$payment_data['total_payments'],
                'successful_payments' => (int)$payment_data['successful_payments']
            ],
            'room_analytics' => [
                'total_room_types' => (int)$room_stats['total_room_types'],
                'total_room_units' => (int)$room_stats['total_room_units'],
                'average_rent' => (float)$room_stats['average_rent']
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
