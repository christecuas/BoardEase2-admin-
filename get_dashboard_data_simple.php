<?php
// Simple Dashboard Data API - Returns basic dashboard statistics
header("Content-Type: application/json");

try {
    require_once 'dbConfig.php';
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get pending registrations count
    $result = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE status = 'pending'");
    $pending_registrations = $result->fetch_assoc()['count'];
    
    // Get total users count
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $total_users = $result->fetch_assoc()['count'];
    
    // Get total boarding houses count
    $result = $conn->query("SELECT COUNT(*) as count FROM boarding_houses");
    $total_boarding_houses = $result->fetch_assoc()['count'];
    
    // Get total bookings count
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
    $total_bookings = $result->fetch_assoc()['count'];
    
    $response = [
        'success' => true,
        'data' => [
            'overview' => [
                'total_users' => (int)$total_users,
                'total_boarding_houses' => (int)$total_boarding_houses,
                'total_bookings' => (int)$total_bookings,
                'pending_registrations' => (int)$pending_registrations
            ],
            'charts' => [
                'user_growth' => [
                    ['month' => '2024-01', 'new_users' => 5],
                    ['month' => '2024-02', 'new_users' => 8],
                    ['month' => '2024-03', 'new_users' => 12],
                    ['month' => '2024-04', 'new_users' => 15],
                    ['month' => '2024-05', 'new_users' => 18],
                    ['month' => '2024-06', 'new_users' => 22]
                ],
                'booking_trends' => [
                    ['month' => '2024-01', 'bookings' => 3],
                    ['month' => '2024-02', 'bookings' => 7],
                    ['month' => '2024-03', 'bookings' => 11],
                    ['month' => '2024-04', 'bookings' => 14],
                    ['month' => '2024-05', 'bookings' => 17],
                    ['month' => '2024-06', 'bookings' => 21]
                ],
                'boarding_houses_by_status' => [
                    ['status' => 'Active', 'count' => 8],
                    ['status' => 'Inactive', 'count' => 2]
                ]
            ],
            'recent_activity' => [
                'recent_users' => [],
                'recent_bookings' => []
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









