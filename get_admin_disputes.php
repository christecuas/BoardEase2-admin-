<?php
// Get Admin Disputes API - Returns disputes and flagged users data
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
    
    $type = $_GET['type'] ?? 'disputes'; // disputes, flagged
    $status = $_GET['status'] ?? 'all'; // all, pending, resolved, active
    
    if ($type === 'disputes') {
        // Get disputes data (using bookings with issues as disputes for now)
        $where_conditions = [];
        $params = [];
        
        if ($status === 'pending') {
            $where_conditions[] = "b.booking_status = 'Pending'";
        } elseif ($status === 'resolved') {
            $where_conditions[] = "b.booking_status IN ('Confirmed', 'Completed')";
        } elseif ($status === 'active') {
            $where_conditions[] = "b.booking_status = 'Confirmed'";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "
            SELECT 
                b.booking_id as dispute_id,
                CONCAT(r1.f_name, ' ', r1.l_name) as complainant_name,
                r1.email as complainant_email,
                r1.phone_number as complainant_phone,
                CONCAT(r2.f_name, ' ', r2.l_name) as respondent_name,
                r2.email as respondent_email,
                bh.bh_name as property_name,
                b.booking_status as dispute_status,
                b.booking_date as dispute_date,
                b.start_date,
                b.end_date,
                'Booking Issue' as dispute_type,
                'Booking status: ' || b.booking_status as dispute_description,
                b.created_at,
                b.updated_at
            FROM bookings b
            JOIN users u1 ON b.user_id = u1.user_id
            JOIN registration r1 ON u1.reg_id = r1.reg_id
            JOIN room_units ru ON b.room_id = ru.room_id
            JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            JOIN users u2 ON bh.user_id = u2.user_id
            JOIN registration r2 ON u2.reg_id = r2.reg_id
            {$where_clause}
            ORDER BY b.booking_date DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $disputes = $stmt->fetchAll();
        
        // Get dispute statistics
        $stmt = $db->prepare("
            SELECT 
                booking_status as status,
                COUNT(*) as count
            FROM bookings
            GROUP BY booking_status
        ");
        $stmt->execute();
        $dispute_stats = $stmt->fetchAll();
        
        $response = [
            'success' => true,
            'data' => [
                'disputes' => $disputes,
                'statistics' => [
                    'by_status' => $dispute_stats
                ],
                'filters' => [
                    'type' => $type,
                    'status' => $status
                ]
            ]
        ];
        
    } else {
        // Get flagged users (users with multiple issues or complaints)
        $where_conditions = [];
        $params = [];
        
        if ($status === 'active') {
            $where_conditions[] = "u.status = 'Active'";
        } elseif ($status === 'suspended') {
            $where_conditions[] = "u.status = 'Inactive'";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "
            SELECT 
                u.user_id,
                CONCAT(r.f_name, ' ', r.l_name) as full_name,
                r.email,
                r.phone_number,
                r.role,
                u.status,
                u.profile_picture,
                u.created_at,
                u.updated_at,
                (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id AND b.booking_status = 'Cancelled') as cancelled_bookings,
                (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id AND b.booking_status = 'Pending') as pending_bookings,
                CASE 
                    WHEN r.role = 'Owner' THEN (
                        SELECT COUNT(*) FROM boarding_houses bh 
                        WHERE bh.user_id = u.user_id AND bh.status = 'Inactive'
                    )
                    ELSE 0
                END as inactive_properties,
                'Multiple Issues' as flag_reason,
                'High cancellation rate or inactive properties' as flag_description
            FROM users u
            JOIN registration r ON u.reg_id = r.reg_id
            {$where_clause}
            HAVING (cancelled_bookings > 2 OR pending_bookings > 5 OR inactive_properties > 1)
            ORDER BY (cancelled_bookings + pending_bookings + inactive_properties) DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $flagged_users = $stmt->fetchAll();
        
        // Get flagged users statistics
        $stmt = $db->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM users
            WHERE user_id IN (
                SELECT DISTINCT user_id FROM (
                    SELECT user_id, COUNT(*) as issues FROM (
                        SELECT user_id FROM bookings WHERE booking_status = 'Cancelled'
                        UNION ALL
                        SELECT user_id FROM bookings WHERE booking_status = 'Pending'
                        UNION ALL
                        SELECT user_id FROM boarding_houses WHERE status = 'Inactive'
                    ) as all_issues
                    GROUP BY user_id
                    HAVING COUNT(*) > 2
                ) as flagged
            )
            GROUP BY status
        ");
        $stmt->execute();
        $flagged_stats = $stmt->fetchAll();
        
        $response = [
            'success' => true,
            'data' => [
                'flagged_users' => $flagged_users,
                'statistics' => [
                    'by_status' => $flagged_stats
                ],
                'filters' => [
                    'type' => $type,
                    'status' => $status
                ]
            ]
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
