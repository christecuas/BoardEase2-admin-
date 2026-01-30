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
    
    // Get booking_id from request
    $bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    
    if ($bookingId == 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Booking ID is required'
        ));
        exit;
    }
    
    // Get payment proof URL from the most recent payment for this booking
    $sql = "
        SELECT 
            payment_proof,
            receipt_url
        FROM payments
        WHERE booking_id = :booking_id
        ORDER BY payment_id DESC, updated_at DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':booking_id' => $bookingId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        $receiptUrl = $payment['receipt_url'];
        $paymentProof = $payment['payment_proof'];
        
        // Prefer receipt_url, fallback to payment_proof
        $proofUrl = null;
        if ($receiptUrl != null && !empty($receiptUrl) && $receiptUrl !== 'null') {
            $proofUrl = $receiptUrl;
        } else if ($paymentProof != null && !empty($paymentProof) && $paymentProof !== 'null') {
            $proofUrl = $paymentProof;
        }
        
        if ($proofUrl) {
            echo json_encode(array(
                'success' => true,
                'payment_proof_url' => $paymentProof,
                'receipt_url' => $receiptUrl,
                'has_proof' => true
            ));
        } else {
            echo json_encode(array(
                'success' => true,
                'payment_proof_url' => '',
                'receipt_url' => '',
                'has_proof' => false
            ));
        }
    } else {
        echo json_encode(array(
            'success' => true,
            'payment_proof_url' => '',
            'receipt_url' => '',
            'has_proof' => false
        ));
    }
    
} catch (PDOException $e) {
    error_log("Database error in get_payment_proof_by_booking.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Error in get_payment_proof_by_booking.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

