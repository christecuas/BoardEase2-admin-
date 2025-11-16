<?php
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user_id and user_type from request
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $userType = isset($_GET['user_type']) ? trim($_GET['user_type']) : 'owner';
    
    if ($userId == 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'User ID is required'
        ));
        exit;
    }
    
    // For owner: Get bookings for boarding houses owned by this user
    if ($userType === 'owner') {
        // Verify owner exists
        $getOwnerSql = "SELECT user_id FROM users WHERE user_id = :user_id";
        $getOwnerStmt = $pdo->prepare($getOwnerSql);
        $getOwnerStmt->execute([':user_id' => $userId]);
        $ownerData = $getOwnerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ownerData) {
            echo json_encode(array(
                'success' => false,
                'error' => 'Owner not found'
            ));
            exit;
        }
        
        // Get pending bookings for boarding houses owned by this owner
        // boarding_houses.user_id refers to users.user_id directly
        $sql = "
            SELECT 
                b.booking_id,
                b.room_id,
                b.user_id as boarder_user_id,
                b.start_date,
                b.end_date,
                b.booking_status as status,
                DATE_FORMAT(b.booking_date, '%Y-%m-%d %H:%i:%s') as booking_date,
                ru.room_number,
                ru.bhr_id,
                bhr.room_name,
                bhr.room_category as rent_type,
                bhr.price as amount,
                bhr.bh_id,
                bh.bh_name as boarding_house_name,
                bh.bh_address as boarding_house_address,
                reg.id as boarder_reg_id,
                reg.first_name,
                reg.middle_name,
                reg.last_name,
                reg.suffix,
                reg.email as boarder_email,
                reg.phone as boarder_phone,
                -- Get the latest payment status for this booking (most recent payment)
                COALESCE((
                    SELECT payment_status 
                    FROM payments p2 
                    WHERE p2.booking_id = b.booking_id 
                    ORDER BY p2.updated_at DESC, p2.payment_id DESC 
                    LIMIT 1
                ), 'Pending') as payment_status,
                '' as notes,
                COALESCE(u_boarder.profile_picture, '') as profile_image
            FROM bookings b
            INNER JOIN room_units ru ON b.room_id = ru.room_id
            INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            INNER JOIN users u_boarder ON b.user_id = u_boarder.user_id
            INNER JOIN registrations reg ON u_boarder.reg_id = reg.id
            WHERE bh.user_id = :owner_user_id
            AND b.booking_status = 'Pending'
            ORDER BY b.booking_date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':owner_user_id' => $userId]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate payment progress for all bookings
        $bookingProgress = array();
        if (!empty($bookings)) {
            $bookingIds = array_column($bookings, 'booking_id');
            $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
            
            $progressSql = "
                SELECT 
                    b.booking_id,
                    COUNT(pb.breakdown_id) as total_periods,
                    -- Count paid periods (ONLY is_paid = 1, not just payment_id IS NOT NULL)
                    SUM(CASE WHEN pb.is_paid = 1 THEN 1 ELSE 0 END) as paid_periods,
                    SUM(CASE WHEN pb.is_paid = 0 OR pb.is_paid IS NULL THEN 1 ELSE 0 END) as unpaid_periods,
                    COALESCE(SUM(pb.amount), 0) as total_amount,
                    -- Total amount paid (only for paid periods where is_paid = 1)
                    COALESCE(SUM(CASE WHEN pb.is_paid = 1 THEN pb.amount ELSE 0 END), 0) as paid_amount,
                    SUM(CASE WHEN pb.period_type = 'month' THEN 1 ELSE 0 END) as total_months,
                    SUM(CASE WHEN pb.period_type = 'month' AND pb.is_paid = 1 THEN 1 ELSE 0 END) as paid_months
                FROM bookings b
                LEFT JOIN payment_breakdowns pb ON b.booking_id = pb.booking_id
                WHERE b.booking_id IN ($placeholders)
                GROUP BY b.booking_id
            ";
            
            $progressStmt = $pdo->prepare($progressSql);
            $progressStmt->execute($bookingIds);
            $progressResults = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($progressResults as $progress) {
                $totalPeriods = intval($progress['total_periods']);
                $paidPeriods = intval($progress['paid_periods']);
                $totalMonths = intval($progress['total_months']);
                $paidMonths = intval($progress['paid_months']);
                
                $bookingProgress[$progress['booking_id']] = array(
                    'total_periods' => $totalPeriods,
                    'paid_periods' => $paidPeriods,
                    'unpaid_periods' => intval($progress['unpaid_periods']),
                    'total_months' => $totalMonths,
                    'paid_months' => $paidMonths,
                    'remaining_months' => $totalMonths > 0 ? max(0, $totalMonths - $paidMonths) : null,
                    'total_amount' => floatval($progress['total_amount']),
                    'paid_amount' => floatval($progress['paid_amount']),
                    'remaining_amount' => floatval($progress['total_amount']) - floatval($progress['paid_amount']),
                    'is_fully_paid' => $totalPeriods > 0 && $paidPeriods >= $totalPeriods,
                    'payment_progress_percent' => $totalPeriods > 0 ? round(($paidPeriods / $totalPeriods) * 100, 2) : 0
                );
            }
        }
        
        // Format the response
        $formattedBookings = array();
        foreach ($bookings as $booking) {
            // Combine first_name, middle_name, last_name, and suffix
            $fullName = trim($booking['first_name']);
            if (!empty($booking['middle_name'])) {
                $fullName .= ' ' . trim($booking['middle_name']);
            }
            $fullName .= ' ' . trim($booking['last_name']);
            if (!empty($booking['suffix'])) {
                $fullName .= ' ' . trim($booking['suffix']);
            }
            
            // Format room name with room number if available
            $roomName = $booking['room_name'];
            if (!empty($booking['room_number'])) {
                $roomName .= ' - ' . $booking['room_number'];
            }
            
            // Format amount with currency
            $amount = number_format((float)$booking['amount'], 2, '.', '');
            
            // Get payment progress for this booking
            $bookingId = (int)$booking['booking_id'];
            $progress = isset($bookingProgress[$bookingId]) ? $bookingProgress[$bookingId] : array(
                'total_periods' => 0,
                'paid_periods' => 0,
                'unpaid_periods' => 0,
                'total_months' => 0,
                'paid_months' => 0,
                'remaining_months' => null,
                'total_amount' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'is_fully_paid' => false,
                'payment_progress_percent' => 0
            );
            
            $formattedBookings[] = array(
                'booking_id' => $bookingId,
                'boarder_id' => (int)$booking['boarder_user_id'],
                'boarder_name' => $fullName,
                'boarder_email' => $booking['boarder_email'] ?? '',
                'boarder_phone' => $booking['boarder_phone'] ?? '',
                'phone' => $booking['boarder_phone'] ?? '',
                'room_id' => (int)$booking['room_id'],
                'room_name' => $roomName,
                'start_date' => $booking['start_date'],
                'end_date' => $booking['end_date'],
                'amount' => $amount,
                'rent_type' => $booking['rent_type'] ?? '',
                'status' => $booking['status'],
                'boarding_house_name' => $booking['boarding_house_name'] ?? '',
                'boarding_house_address' => $booking['boarding_house_address'] ?? '',
                'boarding_house_id' => (int)$booking['bh_id'],
                'booking_date' => $booking['booking_date'],
                'payment_status' => $booking['payment_status'] ?? 'Pending',
                'notes' => $booking['notes'] ?? '',
                'profile_image' => $booking['profile_image'] ?? '',
                // Payment progress information
                'total_periods' => $progress['total_periods'],
                'paid_periods' => $progress['paid_periods'],
                'unpaid_periods' => $progress['unpaid_periods'],
                'total_months_for_booking' => $progress['total_months'],
                'paid_months_for_booking' => $progress['paid_months'],
                'remaining_months_to_pay' => $progress['remaining_months'],
                'total_amount_for_booking' => number_format($progress['total_amount'], 2, '.', ''),
                'paid_amount_for_booking' => number_format($progress['paid_amount'], 2, '.', ''),
                'remaining_amount_to_pay' => number_format($progress['remaining_amount'], 2, '.', ''),
                'is_fully_paid' => $progress['is_fully_paid'],
                'payment_progress_percent' => $progress['payment_progress_percent']
            );
        }
        
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'pending_bookings' => $formattedBookings
            )
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'error' => 'Invalid user type'
        ));
    }
    
} catch (PDOException $e) {
    error_log("Database error in get_pending_bookings.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Error in get_pending_bookings.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

