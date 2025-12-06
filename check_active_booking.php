<?php
// Check if boarder has an active booking (Pending or Confirmed)
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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user_id from request
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
    } else {
        $inputData = $_GET;
    }
    
    $userId = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;
    
    if ($userId <= 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'User ID is required'
        ));
        exit;
    }
    
    // Check if boarder has an active booking (Pending or Confirmed)
    $checkActiveBookingSql = "
        SELECT 
            booking_id, 
            booking_status, 
            start_date, 
            end_date,
            room_id
        FROM bookings
        WHERE user_id = :user_id
        AND booking_status IN ('Pending', 'Confirmed')
        ORDER BY booking_id DESC
        LIMIT 1
    ";
    
    $checkActiveBookingStmt = $pdo->prepare($checkActiveBookingSql);
    $checkActiveBookingStmt->execute([':user_id' => $userId]);
    $activeBooking = $checkActiveBookingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeBooking) {
        // Boarder has an active booking
        echo json_encode(array(
            'success' => true,
            'has_active_booking' => true,
            'booking_id' => intval($activeBooking['booking_id']),
            'booking_status' => $activeBooking['booking_status'],
            'start_date' => $activeBooking['start_date'],
            'end_date' => $activeBooking['end_date']
        ));
    } else {
        // Boarder has no active booking
        echo json_encode(array(
            'success' => true,
            'has_active_booking' => false
        ));
    }
    
} catch (PDOException $e) {
    error_log("Database error in check_active_booking.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Error in check_active_booking.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

