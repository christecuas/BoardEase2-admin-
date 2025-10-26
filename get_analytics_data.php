<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once 'dbConfig.php';

try {
    $conn = new mysqli($servername, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get current date and time
    $currentDate = date('Y-m-d');
    $currentMonth = date('Y-m');
    $currentYear = date('Y');
    
    // 1. USER ANALYTICS
    $userStats = [];
    
    // Total users
    $totalUsersQuery = "SELECT COUNT(*) as total_users FROM users u 
                       JOIN registrations r ON u.reg_id = r.id 
                       WHERE u.status = 'Active' AND r.status = 'approved'";
    $result = $conn->query($totalUsersQuery);
    $userStats['total_users'] = $result->fetch_assoc()['total_users'];
    
    // Users by role
    $roleStatsQuery = "SELECT r.role, COUNT(*) as count 
                      FROM users u 
                      JOIN registrations r ON u.reg_id = r.id 
                      WHERE u.status = 'Active' AND r.status = 'approved'
                      GROUP BY r.role";
    $result = $conn->query($roleStatsQuery);
    $userStats['by_role'] = [];
    while ($row = $result->fetch_assoc()) {
        $userStats['by_role'][$row['role']] = $row['count'];
    }
    
    // New users this month
    $newUsersQuery = "SELECT COUNT(*) as new_users FROM users u 
                     JOIN registrations r ON u.reg_id = r.id 
                     WHERE u.status = 'Active' AND r.status = 'approved' 
                     AND DATE_FORMAT(r.created_at, '%Y-%m') = '$currentMonth'";
    $result = $conn->query($newUsersQuery);
    $userStats['new_users_this_month'] = $result->fetch_assoc()['new_users'];
    
    // 2. BOARDING HOUSE ANALYTICS
    $bhStats = [];
    
    // Total boarding houses
    $totalBHQuery = "SELECT COUNT(*) as total_bh FROM boarding_houses WHERE status = 'Active'";
    $result = $conn->query($totalBHQuery);
    $bhStats['total_boarding_houses'] = $result->fetch_assoc()['total_bh'];
    
    // New boarding houses this month
    $newBHQuery = "SELECT COUNT(*) as new_bh FROM boarding_houses 
                  WHERE status = 'Active' AND DATE_FORMAT(bh_created_at, '%Y-%m') = '$currentMonth'";
    $result = $conn->query($newBHQuery);
    $bhStats['new_boarding_houses_this_month'] = $result->fetch_assoc()['new_bh'];
    
    // 3. ROOM ANALYTICS
    $roomStats = [];
    
    // Total rooms
    $totalRoomsQuery = "SELECT COUNT(*) as total_rooms FROM boarding_house_rooms bhr
                       JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                       WHERE bh.status = 'Active'";
    $result = $conn->query($totalRoomsQuery);
    $roomStats['total_room_types'] = $result->fetch_assoc()['total_rooms'];
    
    // Total room units
    $totalUnitsQuery = "SELECT SUM(bhr.total_rooms) as total_units FROM boarding_house_rooms bhr
                       JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                       WHERE bh.status = 'Active'";
    $result = $conn->query($totalUnitsQuery);
    $roomStats['total_room_units'] = $result->fetch_assoc()['total_units'] ?? 0;
    
    // Available room units
    $availableUnitsQuery = "SELECT COUNT(*) as available_units FROM room_units ru
                           JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                           JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                           WHERE bh.status = 'Active' AND ru.status = 'Available'";
    $result = $conn->query($availableUnitsQuery);
    $roomStats['available_units'] = $result->fetch_assoc()['available_units'];
    
    // Occupied room units
    $occupiedUnitsQuery = "SELECT COUNT(*) as occupied_units FROM room_units ru
                           JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                           JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                           WHERE bh.status = 'Active' AND ru.status = 'Occupied'";
    $result = $conn->query($occupiedUnitsQuery);
    $roomStats['occupied_units'] = $result->fetch_assoc()['occupied_units'];
    
    // Calculate occupancy rate
    $totalUnits = $roomStats['total_room_units'];
    $occupiedUnits = $roomStats['occupied_units'];
    $roomStats['occupancy_rate'] = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;
    
    // 4. BOOKING ANALYTICS
    $bookingStats = [];
    
    // Total bookings
    $totalBookingsQuery = "SELECT COUNT(*) as total_bookings FROM bookings";
    $result = $conn->query($totalBookingsQuery);
    $bookingStats['total_bookings'] = $result->fetch_assoc()['total_bookings'];
    
    // Bookings by status
    $bookingStatusQuery = "SELECT booking_status, COUNT(*) as count FROM bookings GROUP BY booking_status";
    $result = $conn->query($bookingStatusQuery);
    $bookingStats['by_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $bookingStats['by_status'][$row['booking_status']] = $row['count'];
    }
    
    // New bookings this month
    $newBookingsQuery = "SELECT COUNT(*) as new_bookings FROM bookings 
                        WHERE DATE_FORMAT(booking_date, '%Y-%m') = '$currentMonth'";
    $result = $conn->query($newBookingsQuery);
    $bookingStats['new_bookings_this_month'] = $result->fetch_assoc()['new_bookings'];
    
    // 5. PAYMENT ANALYTICS
    $paymentStats = [];
    
    // Total payments
    $totalPaymentsQuery = "SELECT COUNT(*) as total_payments FROM payments";
    $result = $conn->query($totalPaymentsQuery);
    $paymentStats['total_payments'] = $result->fetch_assoc()['total_payments'];
    
    // Total revenue
    $totalRevenueQuery = "SELECT SUM(payment_amount) as total_revenue FROM payments 
                         WHERE payment_status = 'Completed'";
    $result = $conn->query($totalRevenueQuery);
    $paymentStats['total_revenue'] = $result->fetch_assoc()['total_revenue'] ?? 0;
    
    // Revenue this month
    $monthlyRevenueQuery = "SELECT SUM(payment_amount) as monthly_revenue FROM payments 
                           WHERE payment_status = 'Completed' 
                           AND DATE_FORMAT(payment_date, '%Y-%m') = '$currentMonth'";
    $result = $conn->query($monthlyRevenueQuery);
    $paymentStats['monthly_revenue'] = $result->fetch_assoc()['monthly_revenue'] ?? 0;
    
    // Payments by status
    $paymentStatusQuery = "SELECT payment_status, COUNT(*) as count FROM payments GROUP BY payment_status";
    $result = $conn->query($paymentStatusQuery);
    $paymentStats['by_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $paymentStats['by_status'][$row['payment_status']] = $row['count'];
    }
    
    // 6. MESSAGE ANALYTICS
    $messageStats = [];
    
    // Total messages
    $totalMessagesQuery = "SELECT COUNT(*) as total_messages FROM messages";
    $result = $conn->query($totalMessagesQuery);
    $messageStats['total_messages'] = $result->fetch_assoc()['total_messages'];
    
    // Messages this month
    $monthlyMessagesQuery = "SELECT COUNT(*) as monthly_messages FROM messages 
                            WHERE DATE_FORMAT(msg_timestamp, '%Y-%m') = '$currentMonth'";
    $result = $conn->query($monthlyMessagesQuery);
    $messageStats['monthly_messages'] = $result->fetch_assoc()['monthly_messages'];
    
    // 7. NOTIFICATION ANALYTICS
    $notificationStats = [];
    
    // Total notifications
    $totalNotificationsQuery = "SELECT COUNT(*) as total_notifications FROM notifications";
    $result = $conn->query($totalNotificationsQuery);
    $notificationStats['total_notifications'] = $result->fetch_assoc()['total_notifications'];
    
    // Unread notifications
    $unreadNotificationsQuery = "SELECT COUNT(*) as unread_notifications FROM notifications 
                                WHERE notif_status = 'unread'";
    $result = $conn->query($unreadNotificationsQuery);
    $notificationStats['unread_notifications'] = $result->fetch_assoc()['unread_notifications'];
    
    // 8. GROWTH ANALYTICS (Last 6 months)
    $growthStats = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M Y', strtotime("-$i months"));
        
        // Users growth
        $usersGrowthQuery = "SELECT COUNT(*) as users FROM users u 
                            JOIN registrations r ON u.reg_id = r.id 
                            WHERE u.status = 'Active' AND r.status = 'approved' 
                            AND DATE_FORMAT(r.created_at, '%Y-%m') = '$month'";
        $result = $conn->query($usersGrowthQuery);
        $usersCount = $result->fetch_assoc()['users'];
        
        // Boarding houses growth
        $bhGrowthQuery = "SELECT COUNT(*) as boarding_houses FROM boarding_houses 
                         WHERE status = 'Active' AND DATE_FORMAT(bh_created_at, '%Y-%m') = '$month'";
        $result = $conn->query($bhGrowthQuery);
        $bhCount = $result->fetch_assoc()['boarding_houses'];
        
        // Revenue growth
        $revenueGrowthQuery = "SELECT SUM(payment_amount) as revenue FROM payments 
                              WHERE payment_status = 'Completed' 
                              AND DATE_FORMAT(payment_date, '%Y-%m') = '$month'";
        $result = $conn->query($revenueGrowthQuery);
        $revenue = $result->fetch_assoc()['revenue'] ?? 0;
        
        $growthStats[] = [
            'month' => $monthName,
            'users' => $usersCount,
            'boarding_houses' => $bhCount,
            'revenue' => $revenue
        ];
    }
    
    // 9. GEOGRAPHIC ANALYTICS
    $geographicStats = [];
    
    // Users by location
    $userLocationQuery = "SELECT 
        CASE 
            WHEN r.address LIKE '%Cebu%' OR r.address LIKE '%cebu%' THEN 'Cebu City'
            WHEN r.address LIKE '%Bohol%' OR r.address LIKE '%bohol%' OR r.address LIKE '%Calape%' OR r.address LIKE '%calape%' THEN 'Bohol'
            WHEN r.address LIKE '%Manila%' OR r.address LIKE '%manila%' THEN 'Manila'
            WHEN r.address LIKE '%Davao%' OR r.address LIKE '%davao%' THEN 'Davao'
            WHEN r.address LIKE '%Cagayan%' OR r.address LIKE '%cagayan%' THEN 'Cagayan de Oro'
            WHEN r.address LIKE '%Iloilo%' OR r.address LIKE '%iloilo%' THEN 'Iloilo'
            WHEN r.address LIKE '%Bacolod%' OR r.address LIKE '%bacolod%' THEN 'Bacolod'
            WHEN r.address LIKE '%Zamboanga%' OR r.address LIKE '%zamboanga%' THEN 'Zamboanga'
            ELSE 'Other Areas'
        END as location,
        COUNT(*) as user_count
        FROM users u 
        JOIN registrations r ON u.reg_id = r.id 
        WHERE u.status = 'Active' AND r.status = 'approved'
        GROUP BY location
        ORDER BY user_count DESC";
    $result = $conn->query($userLocationQuery);
    $geographicStats['users_by_location'] = [];
    while ($row = $result->fetch_assoc()) {
        $geographicStats['users_by_location'][] = $row;
    }
    
    // Boarding houses by location
    $bhLocationQuery = "SELECT 
        CASE 
            WHEN bh_address LIKE '%Cebu%' OR bh_address LIKE '%cebu%' THEN 'Cebu City'
            WHEN bh_address LIKE '%Bohol%' OR bh_address LIKE '%bohol%' OR bh_address LIKE '%Calape%' OR bh_address LIKE '%calape%' THEN 'Bohol'
            WHEN bh_address LIKE '%Manila%' OR bh_address LIKE '%manila%' THEN 'Manila'
            WHEN bh_address LIKE '%Davao%' OR bh_address LIKE '%davao%' THEN 'Davao'
            WHEN bh_address LIKE '%Cagayan%' OR bh_address LIKE '%cagayan%' THEN 'Cagayan de Oro'
            WHEN bh_address LIKE '%Iloilo%' OR bh_address LIKE '%iloilo%' THEN 'Iloilo'
            WHEN bh_address LIKE '%Bacolod%' OR bh_address LIKE '%bacolod%' THEN 'Bacolod'
            WHEN bh_address LIKE '%Zamboanga%' OR bh_address LIKE '%zamboanga%' THEN 'Zamboanga'
            ELSE 'Other Areas'
        END as location,
        COUNT(*) as boarding_house_count
        FROM boarding_houses 
        WHERE status = 'Active'
        GROUP BY location
        ORDER BY boarding_house_count DESC";
    $result = $conn->query($bhLocationQuery);
    $geographicStats['boarding_houses_by_location'] = [];
    while ($row = $result->fetch_assoc()) {
        $geographicStats['boarding_houses_by_location'][] = $row;
    }
    
    // 10. TOP PERFORMING BOARDING HOUSES
    $topBHQuery = "SELECT bh.bh_name, bh.bh_address, 
                   COUNT(DISTINCT bhr.bhr_id) as room_types,
                   SUM(bhr.total_rooms) as total_units,
                   COUNT(ru.room_id) as occupied_units,
                   ROUND((COUNT(ru.room_id) / SUM(bhr.total_rooms)) * 100, 2) as occupancy_rate
                   FROM boarding_houses bh
                   LEFT JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
                   LEFT JOIN room_units ru ON bhr.bhr_id = ru.bhr_id AND ru.status = 'Occupied'
                   WHERE bh.status = 'Active'
                   GROUP BY bh.bh_id, bh.bh_name, bh.bh_address
                   ORDER BY occupancy_rate DESC, total_units DESC
                   LIMIT 5";
    $result = $conn->query($topBHQuery);
    $topBoardingHouses = [];
    while ($row = $result->fetch_assoc()) {
        $topBoardingHouses[] = $row;
    }
    
    // Compile all analytics
    $analytics = [
        'success' => true,
        'data' => [
            'users' => $userStats,
            'boarding_houses' => $bhStats,
            'rooms' => $roomStats,
            'bookings' => $bookingStats,
            'payments' => $paymentStats,
            'messages' => $messageStats,
            'notifications' => $notificationStats,
            'growth' => $growthStats,
            'geographic' => $geographicStats,
            'top_boarding_houses' => $topBoardingHouses,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($analytics, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
