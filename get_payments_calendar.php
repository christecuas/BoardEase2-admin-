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

header('Content-Type: application/json; charset=utf-8');
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
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data === null) {
        $data = $_POST; // Fallback to POST if JSON is empty
    }
    
    $ownerId = isset($data['owner_id']) ? intval($data['owner_id']) : 0;
    $month = isset($data['month']) ? intval($data['month']) : date('n'); // Current month (1-12)
    $year = isset($data['year']) ? intval($data['year']) : date('Y'); // Current year
    
    if ($ownerId == 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Owner ID is required'
        ));
        exit;
    }
    
    // Get payments from payment_breakdowns table (has due dates)
    // Also get payments from payments table (for payments without breakdowns)
    $sql = "
        SELECT 
            COALESCE(pb.due_date, pb.period_start_date, p.payment_date) as payment_date,
            pb.payment_status as breakdown_status,
            p.payment_status as payment_table_status,
            pb.is_paid as breakdown_is_paid,
            pb.amount as breakdown_amount,
            p.payment_amount as payment_amount,
            pb.breakdown_id,
            p.payment_id,
            b.booking_id,
            b.user_id as boarder_user_id,
            CONCAT(r.first_name, ' ', r.last_name, 
                   CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
            ru.room_number,
            bh.bh_name,
            COALESCE(pb.due_date, pb.period_start_date) as due_date,
            CASE 
                WHEN pb.is_paid = 1 THEN 'Paid'
                WHEN pb.payment_status = 'Cancelled' THEN 'Cancelled'
                WHEN pb.payment_id IS NOT NULL AND COALESCE(p.payment_status, '') = 'Pending' THEN 'For Approval'
                WHEN pb.payment_status = 'Overdue' OR (COALESCE(pb.due_date, pb.period_start_date) < CURDATE() AND COALESCE(pb.payment_status, 'Pending') IN ('Pending', 'Overdue')) THEN 'Overdue'
                WHEN COALESCE(pb.payment_status, 'Pending') = 'Pending' AND COALESCE(pb.due_date, pb.period_start_date) >= CURDATE() THEN 'Pending'
                WHEN pb.payment_status IS NOT NULL AND pb.payment_status != '' THEN pb.payment_status
                WHEN p.payment_status = 'Completed' THEN 'Paid'
                WHEN p.payment_status = 'Partially Paid' THEN 'Partially Paid'
                WHEN p.payment_status = 'Pending' AND DATE(p.payment_date) < CURDATE() THEN 'Overdue'
                WHEN p.payment_status = 'Pending' THEN 'Pending'
                ELSE 'Pending'
            END as final_status,
            COALESCE(pb.amount, p.payment_amount, 0) as amount
        FROM boarding_houses bh
        INNER JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
        INNER JOIN room_units ru ON bhr.bhr_id = ru.bhr_id
        INNER JOIN bookings b ON ru.room_id = b.room_id
        LEFT JOIN payment_breakdowns pb ON b.booking_id = pb.booking_id
        LEFT JOIN payments p ON (pb.payment_id = p.payment_id OR (pb.payment_id IS NULL AND p.booking_id = b.booking_id))
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN registrations r ON u.reg_id = r.id
        WHERE bh.user_id = :owner_id
            AND (
                (pb.due_date IS NOT NULL AND YEAR(pb.due_date) = :year AND MONTH(pb.due_date) = :month)
                OR (pb.due_date IS NULL AND pb.period_start_date IS NOT NULL AND YEAR(pb.period_start_date) = :year AND MONTH(pb.period_start_date) = :month)
                OR (pb.breakdown_id IS NULL AND p.payment_id IS NOT NULL AND YEAR(p.payment_date) = :year AND MONTH(p.payment_date) = :month)
            )
            AND (
                -- Only show payments that are NOT fully paid
                (pb.is_paid = 0 AND COALESCE(pb.payment_status, 'Pending') != 'Paid' AND COALESCE(pb.payment_status, 'Pending') != 'Cancelled')
                OR (pb.breakdown_id IS NULL AND COALESCE(p.payment_status, 'Pending') != 'Completed' AND COALESCE(p.payment_status, 'Pending') != 'Paid')
            )
        ORDER BY payment_date ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':owner_id' => $ownerId,
        ':year' => $year,
        ':month' => $month
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group payments by date
    $paymentsByDate = array();
    
    foreach ($results as $row) {
        $paymentDate = $row['payment_date'];
        $status = $row['final_status'];
        
        // Format date as Y-m-d
        if (is_string($paymentDate)) {
            $dateObj = DateTime::createFromFormat('Y-m-d', substr($paymentDate, 0, 10));
            if ($dateObj) {
                $dateKey = $dateObj->format('Y-m-d');
            } else {
                continue; // Skip invalid dates
            }
        } else {
            continue; // Skip if not a valid date
        }
        
        if (!isset($paymentsByDate[$dateKey])) {
            $paymentsByDate[$dateKey] = array(
                'date' => $dateKey,
                'payments' => array(),
                'status_counts' => array(
                    'Pending' => 0,
                    'Partially Paid' => 0,
                    'Overdue' => 0
                ),
                'total_amount' => 0
            );
        }
        
        // Skip fully paid payments - only show unpaid ones
        if ($status === 'Paid' || $status === 'Completed') {
            continue; // Skip this payment
        }
        
        // All due dates are red - regardless of status
        // Since these are all due dates (from due_date or period_start_date), mark as Overdue (red)
        $calendarStatus = 'Overdue'; // All due dates are red
        
        $paymentsByDate[$dateKey]['status_counts'][$calendarStatus]++;
        $paymentsByDate[$dateKey]['total_amount'] += floatval($row['amount']);
        
        $paymentsByDate[$dateKey]['payments'][] = array(
            'breakdown_id' => $row['breakdown_id'] ? intval($row['breakdown_id']) : null,
            'payment_id' => $row['payment_id'] ? intval($row['payment_id']) : null,
            'booking_id' => intval($row['booking_id']),
            'boarder_user_id' => intval($row['boarder_user_id']),
            'boarder_name' => $row['boarder_name'] ?: 'Unknown',
            'room_number' => $row['room_number'] ?: 'N/A',
            'bh_name' => $row['bh_name'] ?: 'Unknown',
            'amount' => floatval($row['amount']),
            'status' => $status,
            'calendar_status' => $calendarStatus,
            'due_date' => $row['due_date'] ?: null
        );
    }
    
    // Determine dominant status for each date (for calendar color)
    $calendarData = array();
    foreach ($paymentsByDate as $dateKey => $dateData) {
        $statusCounts = $dateData['status_counts'];
        
        // All dates with due dates are red (Overdue color)
        $dominantStatus = 'Overdue'; // All due dates show as red
        
        $calendarData[] = array(
            'date' => $dateKey,
            'dominant_status' => $dominantStatus,
            'payment_count' => count($dateData['payments']),
            'total_amount' => $dateData['total_amount'],
            'status_counts' => $statusCounts,
            'payments' => $dateData['payments']
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'data' => $calendarData,
        'month' => $month,
        'year' => $year,
        'count' => count($calendarData)
    ));
    
} catch (PDOException $e) {
    error_log("Database error in get_payments_calendar.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Server error in get_payments_calendar.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

