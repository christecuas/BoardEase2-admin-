<?php
// Suppress all error output to prevent HTML from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Start output buffering early to catch any output
ob_start();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
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
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data === null) {
        // Try GET or POST
        $data = $_GET;
        if (empty($data)) {
            $data = $_POST;
        }
    }
    
    $bookingId = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
    
    if (function_exists('error_log')) {
        error_log("get_payment_breakdown.php - Request received: booking_id=" . $bookingId);
    }
    
    if ($bookingId == 0) {
        ob_clean();
        echo json_encode(array(
            'success' => false,
            'error' => 'Booking ID is required'
        ));
        ob_end_flush();
        exit;
    }
    
    // Get payment breakdowns for this booking
    $sql = "
        SELECT 
            pb.breakdown_id,
            pb.booking_id,
            pb.payment_id,
            pb.period_type,
            pb.period_number,
            pb.period_label,
            pb.period_start_date,
            pb.period_end_date,
            pb.amount,
            pb.is_selected,
            pb.is_paid,
            pb.due_date,
            pb.payment_status,
            pb.created_at,
            pb.updated_at,
            p.payment_status as payment_status_from_payment,
            p.payment_date as payment_date_from_payment,
            p.payment_proof
        FROM payment_breakdowns pb
        LEFT JOIN payments p ON pb.payment_id = p.payment_id
        WHERE pb.booking_id = :booking_id
        ORDER BY pb.period_start_date ASC, pb.period_number ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':booking_id' => $bookingId]);
    $breakdowns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (function_exists('error_log')) {
        error_log("get_payment_breakdown.php - Found " . count($breakdowns) . " breakdown periods for booking_id: $bookingId");
    }
    
    // Format breakdowns for response
    $formattedBreakdowns = array();
    foreach ($breakdowns as $breakdown) {
        $proofUrl = null;
        if (!empty($breakdown['payment_proof']) && $breakdown['payment_proof'] !== 'null') {
            $proofUrl = $breakdown['payment_proof'];
        }

        $formattedBreakdowns[] = array(
            'breakdown_id' => intval($breakdown['breakdown_id']),
            'booking_id' => intval($breakdown['booking_id']),
            'payment_id' => $breakdown['payment_id'] ? intval($breakdown['payment_id']) : null,
            'period_type' => $breakdown['period_type'],
            'period_number' => intval($breakdown['period_number']),
            'period_label' => $breakdown['period_label'],
            'period_start_date' => $breakdown['period_start_date'],
            'period_end_date' => $breakdown['period_end_date'],
            'amount' => number_format(floatval($breakdown['amount']), 2, '.', ''),
            'is_selected' => intval($breakdown['is_selected']) === 1,
            // CRITICAL: Only mark as paid if is_paid = 1, not just if payment_id exists
            // When booking is created, periods may have payment_id but is_paid = 0
            'is_paid' => intval($breakdown['is_paid']) === 1,
            'due_date' => $breakdown['due_date'],
            // Use payment_status from breakdown, fallback to payment table, default to 'Pending'
            'payment_status' => $breakdown['payment_status'] ?: ($breakdown['payment_status_from_payment'] ?: 'Pending'),
            'payment_date' => $breakdown['payment_date_from_payment'],
            'payment_proof' => $proofUrl,
            'created_at' => $breakdown['created_at'],
            'updated_at' => $breakdown['updated_at']
        );
    }
    
    ob_clean();
    echo json_encode(array(
        'success' => true,
        'data' => array(
            'breakdowns' => $formattedBreakdowns,
            'count' => count($formattedBreakdowns)
        )
    ));
    ob_end_flush();
    
} catch (PDOException $e) {
    if (function_exists('error_log')) {
        error_log("Database error in get_payment_breakdown.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error occurred. Please try again.'
    ));
    ob_end_flush();
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log("Error in get_payment_breakdown.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error occurred. Please try again.'
    ));
    ob_end_flush();
} catch (Error $e) {
    if (function_exists('error_log')) {
        error_log("Fatal error in get_payment_breakdown.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Fatal error occurred. Please try again.'
    ));
    ob_end_flush();
}


