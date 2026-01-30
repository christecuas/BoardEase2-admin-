<?php
// Turn off error reporting to prevent HTML errors
error_reporting(0);
ini_set('display_errors', 0);

// Prevent any output before JSON
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    ob_end_flush();
    exit;
}

// Clean any output that might have been generated
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        ob_clean();
        echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
        ob_end_flush();
        exit;
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
    ob_end_flush();
    exit;
}

$response = array('success' => false, 'payments' => array());

try {
    // Get JSON input or POST data
    $inputData = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST)) {
            $inputData = $_POST;
        } else {
            $jsonInput = file_get_contents('php://input');
            if (!empty($jsonInput)) {
                $decoded = json_decode($jsonInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $inputData = $decoded;
                }
            }
        }
    }

    $user_id = isset($inputData['user_id']) ? intval($inputData['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    $filter_status = isset($inputData['filter_status']) ? $inputData['filter_status'] : (isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all');
    $sort_by = isset($inputData['sort_by']) ? $inputData['sort_by'] : (isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest');
    
    if ($user_id <= 0) {
        ob_clean();
        echo json_encode(array('success' => false, 'error' => 'Invalid user_id'));
        ob_end_flush();
        exit;
    }

    // For 'successful' filter: Query from payment_breakdowns to show each paid breakdown individually
    // For other filters: Query from payments table
    if ($filter_status === 'successful') {
        // Successful: Show each individual paid breakdown as a separate record
        $sql = "SELECT 
                COALESCE(p.payment_id, pb.breakdown_id) as payment_id,
                pb.breakdown_id,
                pb.booking_id,
                pb.amount as payment_amount,
                COALESCE(p.payment_method, 'N/A') as payment_method,
                'Completed' as status,
                COALESCE(DATE_FORMAT(p.payment_date, '%Y-%m-%d %H:%i:%s'), DATE_FORMAT(pb.created_at, '%Y-%m-%d %H:%i:%s')) as payment_date,
                COALESCE(DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s'), DATE_FORMAT(pb.created_at, '%Y-%m-%d %H:%i:%s')) as created_at,
                p.notes,
                p.payment_proof,
                b.start_date,
                b.end_date,
                b.booking_status,
                COALESCE(bh.bh_name, 'Unknown') as boarding_house_name,
                COALESCE(bh.bh_id, 0) as bh_id,
                ru.room_number,
                bhr.room_name,
                bhr.price as monthly_rate,
                CASE 
                    WHEN bh.bh_id IS NOT NULL THEN 
                        (SELECT image_path FROM boarding_house_images WHERE bh_id = bh.bh_id LIMIT 1)
                    ELSE NULL
                END as bh_image_path
            FROM payment_breakdowns pb
            INNER JOIN bookings b ON pb.booking_id = b.booking_id
            LEFT JOIN payments p ON pb.payment_id = p.payment_id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE b.user_id = ?
            AND (pb.is_paid = 1 OR pb.payment_status = 'Paid')";
    } else {
        // For 'pending_approval' and 'all': Query from payments table
        $sql = "SELECT 
                p.payment_id,
                p.booking_id,
                p.payment_amount,
                p.payment_method,
                p.payment_status as status,
                DATE_FORMAT(p.payment_date, '%Y-%m-%d %H:%i:%s') as payment_date,
                DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                p.notes,
                p.payment_proof,
                b.start_date,
                b.end_date,
                b.booking_status,
                COALESCE(bh.bh_name, 'Unknown') as boarding_house_name,
                COALESCE(bh.bh_id, 0) as bh_id,
                ru.room_number,
                bhr.room_name,
                bhr.price as monthly_rate,
                CASE 
                    WHEN bh.bh_id IS NOT NULL THEN 
                        (SELECT image_path FROM boarding_house_images WHERE bh_id = bh.bh_id LIMIT 1)
                    ELSE NULL
                END as bh_image_path
            FROM payments p
            INNER JOIN bookings b ON p.booking_id = b.booking_id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE p.user_id = ?";
        
        // Apply filter for pending_approval
        if ($filter_status === 'pending_approval') {
            // Pending Approval: based on payments table where payment_status = 'Pending'
            $sql .= " AND p.payment_status = 'Pending'";
        }
        // For 'all', no additional WHERE clause - shows all payments
    }
    
    // Apply sorting
    if ($filter_status === 'successful') {
        // For successful filter, we're querying from payment_breakdowns
        switch ($sort_by) {
            case 'oldest':
                $sql .= " ORDER BY pb.created_at ASC, pb.breakdown_id ASC";
                break;
            case 'amount_asc':
                $sql .= " ORDER BY pb.amount ASC";
                break;
            case 'amount_desc':
                $sql .= " ORDER BY pb.amount DESC";
                break;
            case 'status':
                $sql .= " ORDER BY pb.payment_status ASC, pb.created_at DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY pb.created_at DESC, pb.breakdown_id DESC";
                break;
        }
    } else {
        // For other filters, we're querying from payments
        switch ($sort_by) {
            case 'oldest':
                $sql .= " ORDER BY p.payment_date ASC, p.created_at ASC";
                break;
            case 'amount_asc':
                $sql .= " ORDER BY p.payment_amount ASC";
                break;
            case 'amount_desc':
                $sql .= " ORDER BY p.payment_amount DESC";
                break;
            case 'status':
                $sql .= " ORDER BY p.payment_status ASC, p.payment_date DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";
                break;
        }
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $errorMsg = $conn->error ? $conn->error : 'Unknown error';
        throw new Exception('Database query preparation failed: ' . $errorMsg);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        $errorMsg = $stmt->error ? $stmt->error : 'Unknown error';
        $stmt->close();
        throw new Exception('Database query execution failed: ' . $errorMsg);
    }

    $result = $stmt->get_result();
    if (!$result) {
        $stmt->close();
        throw new Exception('Failed to get query result');
    }
    
    $payments = array();
    if ($result->num_rows > 0) {
        $payments = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    // ========== PAYMENT SUMMARY ==========
    // Calculate total amount paid (only Completed payments)
    $totalPaidSql = "SELECT COALESCE(SUM(payment_amount), 0) as total_paid 
                     FROM payments 
                     WHERE user_id = ? AND payment_status = 'Completed'";
    $totalPaidStmt = $conn->prepare($totalPaidSql);
    $totalPaidStmt->bind_param("i", $user_id);
    $totalPaidStmt->execute();
    $totalPaidResult = $totalPaidStmt->get_result();
    $totalPaidData = $totalPaidResult->fetch_assoc();
    $totalAmountPaid = floatval($totalPaidData['total_paid']);
    $totalPaidStmt->close();

    // Calculate total remaining balance from unpaid payment breakdowns
    $totalBalanceSql = "SELECT COALESCE(SUM(pb.amount), 0) as total_balance
                        FROM payment_breakdowns pb
                        INNER JOIN bookings b ON pb.booking_id = b.booking_id
                        WHERE b.user_id = ? 
                        AND pb.is_paid = 0 
                        AND pb.payment_status != 'Cancelled'";
    $totalBalanceStmt = $conn->prepare($totalBalanceSql);
    $totalBalanceStmt->bind_param("i", $user_id);
    $totalBalanceStmt->execute();
    $totalBalanceResult = $totalBalanceStmt->get_result();
    $totalBalanceData = $totalBalanceResult->fetch_assoc();
    $totalRemainingBalance = floatval($totalBalanceData['total_balance']);
    $totalBalanceStmt->close();

    // Get next due date (earliest unpaid breakdown with due_date)
    $nextDueDateSql = "SELECT MIN(pb.due_date) as next_due_date
                       FROM payment_breakdowns pb
                       INNER JOIN bookings b ON pb.booking_id = b.booking_id
                       WHERE b.user_id = ? 
                       AND pb.is_paid = 0 
                       AND pb.payment_status NOT IN ('Cancelled', 'Paid')
                       AND pb.due_date IS NOT NULL
                       AND pb.due_date >= CURDATE()";
    $nextDueDateStmt = $conn->prepare($nextDueDateSql);
    $nextDueDateStmt->bind_param("i", $user_id);
    $nextDueDateStmt->execute();
    $nextDueDateResult = $nextDueDateStmt->get_result();
    $nextDueDateData = $nextDueDateResult->fetch_assoc();
    $nextDueDate = $nextDueDateData['next_due_date'] ? $nextDueDateData['next_due_date'] : null;
    $nextDueDateStmt->close();

    // Determine rental status for active bookings
    $rentalStatusSql = "SELECT 
                            b.booking_id,
                            b.booking_status,
                            b.start_date,
                            b.end_date,
                            CASE 
                                WHEN b.booking_status = 'Pending' THEN 'Pending Approval'
                                WHEN b.booking_status = 'Cancelled' THEN 'Cancelled'
                                WHEN b.booking_status = 'Completed' THEN 'Expired'
                                WHEN CURDATE() > b.end_date THEN 'Expired'
                                WHEN b.end_date IS NULL OR CURDATE() < b.start_date THEN 'Pending Approval'
                                WHEN EXISTS (
                                    SELECT 1 FROM payment_breakdowns pb 
                                    WHERE pb.booking_id = b.booking_id 
                                    AND pb.is_paid = 0 
                                    AND pb.due_date < CURDATE()
                                    AND pb.payment_status != 'Cancelled'
                                ) THEN 'Overdue'
                                ELSE 'Active'
                            END as rental_status
                        FROM bookings b
                        WHERE b.user_id = ?
                        AND b.booking_status IN ('Pending', 'Confirmed')
                        AND (b.end_date IS NULL OR CURDATE() <= b.end_date)
                        ORDER BY b.start_date DESC
                        LIMIT 1";
    $rentalStatusStmt = $conn->prepare($rentalStatusSql);
    $rentalStatusStmt->bind_param("i", $user_id);
    $rentalStatusStmt->execute();
    $rentalStatusResult = $rentalStatusStmt->get_result();
    $rentalStatusData = $rentalStatusResult->fetch_assoc();
    $rentalStatus = $rentalStatusData ? $rentalStatusData['rental_status'] : 'No Active Rental';
    $rentalStatusStmt->close();

    // Build payment summary
    $paymentSummary = array(
        'total_amount_paid' => "₱" . number_format($totalAmountPaid, 2, '.', ','),
        'total_amount_paid_raw' => $totalAmountPaid,
        'total_remaining_balance' => "₱" . number_format($totalRemainingBalance, 2, '.', ','),
        'total_remaining_balance_raw' => $totalRemainingBalance,
        'next_due_date' => $nextDueDate,
        'rental_status' => $rentalStatus
    );

    // ========== FORMAT PAYMENTS ==========
    $baseUrl = 'https://boardease.calapebohol.com/';
    $formattedPayments = array();
    
    if (is_array($payments) && count($payments) > 0) {
        foreach ($payments as $payment) {
            // Skip invalid payments
            if (!is_array($payment) || !isset($payment['payment_id'])) {
                continue;
            }
            
            $paymentId = (int)$payment['payment_id'];
            $bookingId = (int)$payment['booking_id'];
            $breakdownId = isset($payment['breakdown_id']) ? (int)$payment['breakdown_id'] : null;
            
            // Get payment breakdowns - since main query is already filtered, get matching breakdowns
            // For each filter, get the breakdowns that match the filter criteria
            if ($filter_status === 'successful') {
                // For successful: Since each record is already a paid breakdown, get that specific breakdown
                // Show only the breakdown that matches this record
                if ($breakdownId) {
                    $breakdownSql = "SELECT 
                                        pb.breakdown_id,
                                        pb.period_type,
                                        pb.period_number,
                                        pb.period_label,
                                        pb.period_start_date,
                                        pb.period_end_date,
                                        pb.amount,
                                        pb.is_paid,
                                        pb.due_date,
                                        pb.payment_status,
                                        DATE_FORMAT(pb.created_at, '%Y-%m-%d') as breakdown_date
                                    FROM payment_breakdowns pb
                                    WHERE pb.breakdown_id = ?
                                    ORDER BY pb.period_start_date ASC, pb.breakdown_id ASC";
                    $breakdownStmt = $conn->prepare($breakdownSql);
                    $breakdownStmt->bind_param("i", $breakdownId);
                } else {
                    // Fallback: get all paid breakdowns for this booking
                    $breakdownSql = "SELECT 
                                        pb.breakdown_id,
                                        pb.period_type,
                                        pb.period_number,
                                        pb.period_label,
                                        pb.period_start_date,
                                        pb.period_end_date,
                                        pb.amount,
                                        pb.is_paid,
                                        pb.due_date,
                                        pb.payment_status,
                                        DATE_FORMAT(pb.created_at, '%Y-%m-%d') as breakdown_date
                                    FROM payment_breakdowns pb
                                    WHERE pb.booking_id = ?
                                    AND (pb.is_paid = 1 OR pb.payment_status = 'Paid')
                                    ORDER BY pb.period_start_date ASC, pb.breakdown_id ASC";
                    $breakdownStmt = $conn->prepare($breakdownSql);
                    $breakdownStmt->bind_param("i", $bookingId);
                }
            } elseif ($filter_status === 'pending_approval') {
                // For pending approval: get breakdowns linked to this pending payment
                // Since the main query already filters for payment_status = 'Pending', get breakdowns for this payment
                $breakdownSql = "SELECT 
                                    pb.breakdown_id,
                                    pb.period_type,
                                    pb.period_number,
                                    pb.period_label,
                                    pb.period_start_date,
                                    pb.period_end_date,
                                    pb.amount,
                                    pb.is_paid,
                                    pb.due_date,
                                    pb.payment_status,
                                    DATE_FORMAT(pb.created_at, '%Y-%m-%d') as breakdown_date
                                FROM payment_breakdowns pb
                                WHERE pb.booking_id = ?
                                AND (pb.payment_id = ? OR pb.payment_id IS NULL)
                                ORDER BY pb.period_start_date ASC, pb.breakdown_id ASC";
                $breakdownStmt = $conn->prepare($breakdownSql);
                $breakdownStmt->bind_param("ii", $bookingId, $paymentId);
            } else {
                // For 'all': get all breakdowns for this booking
                $breakdownSql = "SELECT 
                                    pb.breakdown_id,
                                    pb.period_type,
                                    pb.period_number,
                                    pb.period_label,
                                    pb.period_start_date,
                                    pb.period_end_date,
                                    pb.amount,
                                    pb.is_paid,
                                    pb.due_date,
                                    pb.payment_status,
                                    DATE_FORMAT(pb.created_at, '%Y-%m-%d') as breakdown_date
                                FROM payment_breakdowns pb
                                WHERE pb.booking_id = ?
                                ORDER BY pb.period_start_date ASC, pb.breakdown_id ASC";
                $breakdownStmt = $conn->prepare($breakdownSql);
                $breakdownStmt->bind_param("i", $bookingId);
            }
            $breakdownStmt->execute();
            $breakdownResult = $breakdownStmt->get_result();
            $breakdowns = array();
            $installmentNumber = 0;
            
            while ($breakdown = $breakdownResult->fetch_assoc()) {
                $installmentNumber++;
                $breakdowns[] = array(
                    'breakdown_id' => (int)$breakdown['breakdown_id'],
                    'installment_number' => $installmentNumber,
                    'period_label' => $breakdown['period_label'],
                    'period_start_date' => $breakdown['period_start_date'],
                    'period_end_date' => $breakdown['period_end_date'],
                    'amount' => "₱" . number_format((float)$breakdown['amount'], 2, '.', ','),
                    'amount_raw' => (float)$breakdown['amount'],
                    'breakdown_date' => $breakdown['breakdown_date'],
                    'is_paid' => (bool)$breakdown['is_paid'],
                    'due_date' => $breakdown['due_date'],
                    'payment_status' => $breakdown['payment_status']
                );
            }
            $breakdownStmt->close();
            
            // Determine enhanced payment status based on payments table
            $status = isset($payment['status']) ? $payment['status'] : 'Pending';
            $enhancedStatus = $status;
            
            // Base status on payments table status
            if ($status === 'Fully Paid' || $status === 'Partially Paid' || $status === 'Paid' || $status === 'Completed') {
                // Fully Paid or Partially Paid = Successful
                $enhancedStatus = 'Successful';
            } elseif ($status === 'Pending') {
                // Pending = Pending for Approval
                $enhancedStatus = 'Pending for Approval';
            } else {
                // Keep original status for other cases
                $enhancedStatus = $status;
            }
            
            // Get next due date for this payment
            $nextDueDateForPayment = null;
            if (count($breakdowns) > 0) {
                foreach ($breakdowns as $bd) {
                    if (!$bd['is_paid'] && $bd['due_date'] && ($nextDueDateForPayment === null || strtotime($bd['due_date']) < strtotime($nextDueDateForPayment))) {
                        $nextDueDateForPayment = $bd['due_date'];
                    }
                }
            }
            
            // Calculate days late
            $daysLate = null;
            if ($status === 'Completed' && $nextDueDateForPayment && isset($payment['payment_date'])) {
                try {
                    $dueDateObj = new DateTime($nextDueDateForPayment);
                    $paymentDateObj = new DateTime($payment['payment_date']);
                    if ($paymentDateObj > $dueDateObj) {
                        $daysLate = $dueDateObj->diff($paymentDateObj)->days;
                    }
                } catch (Exception $e) {
                    // Ignore date parsing errors
                }
            }
            
            // Format amount
            $paymentAmount = isset($payment['payment_amount']) ? (float)$payment['payment_amount'] : 0.0;
            $amount = "₱" . number_format($paymentAmount, 2, '.', ',');
            
            // Format boarding house image URL
            $rawImagePath = isset($payment['bh_image_path']) ? $payment['bh_image_path'] : "";
            $bhImageUrl = "";
            if (!empty($rawImagePath)) {
                if (strpos($rawImagePath, 'http://') === 0 || strpos($rawImagePath, 'https://') === 0) {
                    $bhImageUrl = $rawImagePath;
                } else {
                    $cleanPath = ltrim($rawImagePath, '/');
                    if (strpos($cleanPath, 'uploads/') !== 0) {
                        $cleanPath = 'uploads/' . $cleanPath;
                    }
                    $bhImageUrl = $baseUrl . $cleanPath;
                }
            }
            
            // Format payment proof URL
            $paymentProofUrl = "";
            $rawProofPath = isset($payment['payment_proof']) ? $payment['payment_proof'] : "";
            if (!empty($rawProofPath)) {
                if (strpos($rawProofPath, 'http://') === 0 || strpos($rawProofPath, 'https://') === 0) {
                    $paymentProofUrl = $rawProofPath;
                } else {
                    $cleanProofPath = ltrim($rawProofPath, '/');
                    if (strpos($cleanProofPath, 'uploads/') !== 0) {
                        $cleanProofPath = 'uploads/' . $cleanProofPath;
                    }
                    $paymentProofUrl = $baseUrl . $cleanProofPath;
                }
            }
            
            // Room information
            $roomInfo = "";
            $roomName = isset($payment['room_name']) ? $payment['room_name'] : '';
            $roomNumber = isset($payment['room_number']) ? $payment['room_number'] : '';
            if (!empty($roomName)) {
                $roomInfo = $roomName;
                if (!empty($roomNumber)) {
                    $roomInfo .= " - " . $roomNumber;
                }
            } else if (!empty($roomNumber)) {
                $roomInfo = $roomNumber;
            }
            
            // Monthly rate
            $monthlyRate = isset($payment['monthly_rate']) ? (float)$payment['monthly_rate'] : 0.0;
            $monthlyRateFormatted = $monthlyRate > 0 ? "₱" . number_format($monthlyRate, 2, '.', ',') : '';
        
                $formattedPayments[] = array(
                'payment_id' => $paymentId,
                'booking_id' => $bookingId,
                'boarding_house_name' => isset($payment['boarding_house_name']) ? $payment['boarding_house_name'] : '',
                'room_info' => $roomInfo,
                'room_name' => $roomName,
                'room_number' => $roomNumber,
                'monthly_rate' => $monthlyRateFormatted,
                'monthly_rate_raw' => $monthlyRate,
                'amount' => $amount,
                'raw_amount' => $paymentAmount,
                'payment_date' => isset($payment['payment_date']) ? $payment['payment_date'] : (isset($payment['created_at']) ? $payment['created_at'] : ''),
                'status' => $status,
                'payment_status' => $enhancedStatus,
                'payment_method' => isset($payment['payment_method']) ? $payment['payment_method'] : '',
                'notes' => isset($payment['notes']) ? $payment['notes'] : '',
                'notes' => isset($payment['notes']) ? $payment['notes'] : '',
                'bh_image_url' => $bhImageUrl,
                'payment_proof_url' => $paymentProofUrl,
                'payment_breakdown' => $breakdowns,
                'due_date' => $nextDueDateForPayment,
                'days_late' => $daysLate,
                'start_date' => isset($payment['start_date']) ? $payment['start_date'] : null,
                'end_date' => isset($payment['end_date']) ? $payment['end_date'] : null,
                'booking_status' => isset($payment['booking_status']) ? $payment['booking_status'] : null
            );
        }
    }

    // ========== RUNNING LEDGER ==========
    // Calculate total amount due for all bookings
    $totalAmountDueSql = "SELECT COALESCE(SUM(pb.amount), 0) as total_due
                          FROM payment_breakdowns pb
                          INNER JOIN bookings b ON pb.booking_id = b.booking_id
                          WHERE b.user_id = ?
                          AND pb.payment_status != 'Cancelled'";
    $totalAmountDueStmt = $conn->prepare($totalAmountDueSql);
    $totalAmountDueStmt->bind_param("i", $user_id);
    $totalAmountDueStmt->execute();
    $totalAmountDueResult = $totalAmountDueStmt->get_result();
    $totalAmountDueData = $totalAmountDueResult->fetch_assoc();
    $totalAmountDue = floatval($totalAmountDueData['total_due']);
    $totalAmountDueStmt->close();
    
    // Calculate total amount paid (from payments table - Completed status)
    $ledgerAmountPaid = $totalAmountPaid; // Already calculated above
    
    // Calculate balance forward (initial balance before any payments)
    $balanceForward = $totalAmountDue - $ledgerAmountPaid - $totalRemainingBalance;
    if ($balanceForward < 0) $balanceForward = 0;
    
    // Remaining balance is already calculated above
    $remainingBalance = $totalRemainingBalance;
    
    $runningLedger = array(
        'amount_due' => "₱" . number_format($totalAmountDue, 2, '.', ','),
        'amount_due_raw' => $totalAmountDue,
        'amount_paid' => "₱" . number_format($ledgerAmountPaid, 2, '.', ','),
        'amount_paid_raw' => $ledgerAmountPaid,
        'balance_forward' => "₱" . number_format($balanceForward, 2, '.', ','),
        'balance_forward_raw' => $balanceForward,
        'remaining_balance' => "₱" . number_format($remainingBalance, 2, '.', ','),
        'remaining_balance_raw' => $remainingBalance
    );

    // ========== FILTERING & SORTING OPTIONS ==========
    $filterOptions = array(
        'all' => 'All Payments',
        'successful' => 'Successful',
        'pending_approval' => 'Pending Approval'
    );
    
    $sortOptions = array(
        'newest' => 'Newest to Oldest',
        'oldest' => 'Oldest to Newest',
        'amount_asc' => 'Amount (Low to High)',
        'amount_desc' => 'Amount (High to Low)',
        'status' => 'Status'
    );

    // Build complete response
    $response['success'] = true;
    $response['payment_summary'] = $paymentSummary;
    $response['running_ledger'] = $runningLedger;
    $response['payments'] = $formattedPayments;
    $response['filter_options'] = $filterOptions;
    $response['sort_options'] = $sortOptions;
    $response['current_filter'] = $filter_status;
    $response['current_sort'] = $sort_by;
    $response['total_payments'] = count($formattedPayments);

} catch (Exception $e) {
    // Log error for debugging
    error_log("Payment History Error: " . $e->getMessage());
    error_log("Payment History Error Trace: " . $e->getTraceAsString());
    $response['success'] = false;
    $response['error'] = 'An error occurred: ' . $e->getMessage();
    $response['payment_summary'] = array(
        'total_amount_paid' => "₱0.00",
        'total_amount_paid_raw' => 0,
        'total_remaining_balance' => "₱0.00",
        'total_remaining_balance_raw' => 0,
        'next_due_date' => null,
        'rental_status' => 'No Active Rental'
    );
    $response['running_ledger'] = array(
        'amount_due' => "₱0.00",
        'amount_due_raw' => 0,
        'amount_paid' => "₱0.00",
        'amount_paid_raw' => 0,
        'balance_forward' => "₱0.00",
        'balance_forward_raw' => 0,
        'remaining_balance' => "₱0.00",
        'remaining_balance_raw' => 0
    );
    $response['payments'] = array();
    $response['total_payments'] = 0;
} catch (Error $e) {
    // Catch fatal errors
    error_log("Payment History Fatal Error: " . $e->getMessage());
    error_log("Payment History Fatal Error Trace: " . $e->getTraceAsString());
    $response['success'] = false;
    $response['error'] = 'A fatal error occurred: ' . $e->getMessage();
    $response['payment_summary'] = array(
        'total_amount_paid' => "₱0.00",
        'total_amount_paid_raw' => 0,
        'total_remaining_balance' => "₱0.00",
        'total_remaining_balance_raw' => 0,
        'next_due_date' => null,
        'rental_status' => 'No Active Rental'
    );
    $response['running_ledger'] = array(
        'amount_due' => "₱0.00",
        'amount_due_raw' => 0,
        'amount_paid' => "₱0.00",
        'amount_paid_raw' => 0,
        'balance_forward' => "₱0.00",
        'balance_forward_raw' => 0,
        'remaining_balance' => "₱0.00",
        'remaining_balance_raw' => 0
    );
    $response['payments'] = array();
    $response['total_payments'] = 0;
}

// Clean output buffer and send JSON
ob_clean();
echo json_encode($response, JSON_UNESCAPED_SLASHES);
ob_end_flush();

if (isset($conn)) {
    $conn->close();
}
