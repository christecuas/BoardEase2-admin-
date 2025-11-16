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

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
    exit;
}

$response = array('success' => false, 'payments' => array());

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
if ($user_id <= 0) {
    echo json_encode(array('success' => false, 'error' => 'Invalid user_id'));
    exit;
}

// Get all payments for boarding houses owned by this owner
$sql = "SELECT 
            p.payment_id,
            p.booking_id,
            p.payment_amount,
            p.payment_method,
            p.payment_status as status,
            DATE_FORMAT(p.payment_date, '%Y-%m-%d %H:%i:%s') as payment_date,
            DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
            p.notes,
            bh.bh_name as boarding_house_name,
            bh.bh_id,
            CONCAT(reg.first_name, ' ', COALESCE(reg.middle_name, ''), ' ', reg.last_name, ' ', COALESCE(reg.suffix, '')) as boarder_name,
            (SELECT image_path FROM boarding_house_images WHERE bh_id = bh.bh_id LIMIT 1) as bh_image_path
        FROM payments p
        INNER JOIN bookings b ON p.booking_id = b.booking_id
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        INNER JOIN users u_boarder ON b.user_id = u_boarder.user_id
        INNER JOIN registrations reg ON u_boarder.reg_id = reg.id
        WHERE bh.user_id = ?
        ORDER BY p.payment_date DESC, p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);

// Format payments
$formattedPayments = array();
foreach ($payments as $payment) {
    $fullName = trim($payment['boarder_name']);
    
    // Create description based on status
    $description = "";
    switch ($payment['status']) {
        case 'Completed':
        case 'Paid':
            $description = "Payment completed successfully";
            break;
        case 'Pending':
            $description = "Payment pending confirmation";
            break;
        case 'Failed':
        case 'Cancelled':
            $description = "Payment failed or cancelled";
            break;
        default:
            $description = "Payment status: " . $payment['status'];
    }
    
    // Format amount
    $amount = "₱" . number_format((float)$payment['payment_amount'], 2, '.', ',');
    
    // Format boarding house image URL
    $baseUrl = 'https://hookiest-unprotecting-cher.ngrok-free.dev/BoardEase2/';
    $rawImagePath = $payment['bh_image_path'] ?? "";
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
    
    $formattedPayments[] = array(
        'payment_id' => (int)$payment['payment_id'],
        'boarder_name' => $fullName,
        'boarding_house_name' => $payment['boarding_house_name'] ?? '',
        'amount' => $amount,
        'payment_date' => $payment['payment_date'] ?? $payment['created_at'],
        'status' => $payment['status'],
        'description' => $description,
        'bh_image_url' => $bhImageUrl
    );
}

$response['success'] = true;
$response['payments'] = $formattedPayments;

echo json_encode($response, JSON_UNESCAPED_SLASHES);
?>

