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

header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

include 'dbConfig.php';

$response = array('success' => false, 'bookings' => array(), 'bh_name' => '');

// Get JSON input or POST data
$inputData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST)) {
        $inputData = $_POST;
    } else {
        // Try to get JSON input
        $jsonInput = file_get_contents('php://input');
        if (!empty($jsonInput)) {
            $decoded = json_decode($jsonInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $inputData = $decoded;
            }
        }
    }
}

// --- Validate user_id and bh_id ---
$user_id = isset($inputData['user_id']) ? intval($inputData['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
$bh_id = isset($inputData['bh_id']) ? intval($inputData['bh_id']) : (isset($_GET['bh_id']) ? intval($_GET['bh_id']) : 0);

if ($user_id <= 0 || $bh_id <= 0) {
    error_log("get_popular_listing_bookings.php - Invalid user_id or bh_id. user_id: $user_id, bh_id: $bh_id. Input data: " . json_encode($inputData));
    echo json_encode(array('success' => false, 'error' => 'Invalid user_id or bh_id'));
    exit;
}

// Verify that the boarding house belongs to this owner
$verifySql = "SELECT bh_id, bh_name FROM boarding_houses WHERE bh_id = ? AND user_id = ?";
$verifyStmt = $conn->prepare($verifySql);
$verifyStmt->bind_param("ii", $bh_id, $user_id);
$verifyStmt->execute();
$verifyResult = $verifyStmt->get_result();
$bhData = $verifyResult->fetch_assoc();

if (!$bhData) {
    echo json_encode(array('success' => false, 'error' => 'Boarding house not found or does not belong to owner'));
    exit;
}

$response['bh_name'] = $bhData['bh_name'];

// Get all confirmed bookings for this boarding house
$sql = "SELECT 
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
            reg.first_name,
            reg.middle_name,
            reg.last_name,
            reg.suffix,
            reg.email as boarder_email,
            reg.phone as boarder_phone,
            COALESCE(u_boarder.profile_picture, '') as profile_image
        FROM bookings b
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        INNER JOIN users u_boarder ON b.user_id = u_boarder.user_id
        INNER JOIN registrations reg ON u_boarder.reg_id = reg.id
        WHERE bh.bh_id = ?
        AND b.booking_status = 'Confirmed'
        ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bh_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Format bookings
$formattedBookings = array();
foreach ($bookings as $booking) {
    // Combine name
    $fullName = trim($booking['first_name']);
    if (!empty($booking['middle_name'])) {
        $fullName .= ' ' . trim($booking['middle_name']);
    }
    $fullName .= ' ' . trim($booking['last_name']);
    if (!empty($booking['suffix'])) {
        $fullName .= ' ' . trim($booking['suffix']);
    }
    
    // Format room name
    $roomName = $booking['room_name'];
    if (!empty($booking['room_number'])) {
        $roomName .= ' - ' . $booking['room_number'];
    }
    
    $formattedBookings[] = array(
        'booking_id' => (int)$booking['booking_id'],
        'boarder_id' => (int)$booking['boarder_user_id'],
        'boarder_name' => $fullName,
        'boarder_email' => $booking['boarder_email'] ?? '',
        'boarder_phone' => $booking['boarder_phone'] ?? '',
        'room_id' => (int)$booking['room_id'],
        'room_name' => $roomName,
        'start_date' => $booking['start_date'],
        'end_date' => $booking['end_date'],
        'amount' => number_format((float)$booking['amount'], 2, '.', ''),
        'rent_type' => $booking['rent_type'] ?? '',
        'status' => $booking['status'],
        'boarding_house_name' => $booking['boarding_house_name'] ?? '',
        'boarding_house_address' => $booking['boarding_house_address'] ?? '',
        'boarding_house_id' => (int)$booking['bh_id'],
        'booking_date' => $booking['booking_date'],
        'profile_image' => $booking['profile_image'] ?? ''
    );
}

$response['success'] = true;
$response['bookings'] = $formattedBookings;

echo json_encode($response, JSON_UNESCAPED_SLASHES);
?>

