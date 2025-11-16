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

$response = array('success' => false, 'rentals' => array());

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

// Get all confirmed/completed bookings (rentals) for boarding houses owned by this owner
$sql = "SELECT 
            b.booking_id,
            b.start_date,
            b.end_date,
            b.booking_status as status,
            DATE_FORMAT(b.booking_date, '%Y-%m-%d %H:%i:%s') as booking_date,
            DATE_FORMAT(b.start_date, '%Y-%m-%d') as start_date_formatted,
            DATE_FORMAT(b.end_date, '%Y-%m-%d') as end_date_formatted,
            bhr.room_name,
            ru.room_number,
            bh.bh_name as boarding_house_name,
            CONCAT(reg.first_name, ' ', COALESCE(reg.middle_name, ''), ' ', reg.last_name, ' ', COALESCE(reg.suffix, '')) as boarder_name,
            COALESCE(u_boarder.profile_picture, '') as profile_image
        FROM bookings b
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        INNER JOIN users u_boarder ON b.user_id = u_boarder.user_id
        INNER JOIN registrations reg ON u_boarder.reg_id = reg.id
        WHERE bh.user_id = ?
        AND b.booking_status IN ('Confirmed', 'Completed')
        ORDER BY b.end_date DESC, b.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rentals = $result->fetch_all(MYSQLI_ASSOC);

// Format rentals - Current/Active or Completed
$formattedRentals = array();
$currentDate = date('Y-m-d');

foreach ($rentals as $rental) {
    $fullName = trim($rental['boarder_name']);
    
    // Determine rental status: Current/Active or Completed
    $status = "";
    $description = "";
    
    $startDate = $rental['start_date_formatted'];
    $endDate = $rental['end_date_formatted'];
    
    // Check if booking is completed (status is Completed OR end_date has passed)
    if ($rental['status'] == 'Completed') {
        $status = "Completed";
        $description = "Stay completed on " . $endDate;
    } else if ($rental['status'] == 'Confirmed') {
        // Check if current date is past end_date
        if ($endDate && $currentDate > $endDate) {
            $status = "Completed";
            $description = "Stay completed on " . $endDate;
        } else {
            // Still active/current - end_date is in the future or null
            $status = "Current";
            if ($endDate) {
                $description = "Currently boarding until " . $endDate;
            } else {
                $description = "Currently boarding (started " . $startDate . ")";
            }
        }
    }
    
    // Format room name with room number if available
    $roomName = $rental['room_name'];
    if (!empty($rental['room_number'])) {
        $roomName .= " - " . $rental['room_number'];
    }
    
    // Format boarder profile image URL
    $baseUrl = 'https://hookiest-unprotecting-cher.ngrok-free.dev/BoardEase2/';
    $rawProfilePath = $rental['profile_image'] ?? "";
    $profileImageUrl = "";
    
    if (!empty($rawProfilePath)) {
        if (strpos($rawProfilePath, 'http://') === 0 || strpos($rawProfilePath, 'https://') === 0) {
            $profileImageUrl = $rawProfilePath;
        } else {
            $cleanPath = ltrim($rawProfilePath, '/');
            if (strpos($cleanPath, 'uploads/') !== 0) {
                $cleanPath = 'uploads/' . $cleanPath;
            }
            $profileImageUrl = $baseUrl . $cleanPath;
        }
    }
    
    $formattedRentals[] = array(
        'booking_id' => (int)$rental['booking_id'],
        'boarder_name' => $fullName,
        'boarding_house_name' => $rental['boarding_house_name'] ?? '',
        'room_name' => $roomName,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'booking_date' => $rental['booking_date'],
        'status' => $status,
        'description' => $description,
        'profile_image_url' => $profileImageUrl
    );
}

$response['success'] = true;
$response['rentals'] = $formattedRentals;

echo json_encode($response, JSON_UNESCAPED_SLASHES);
?>

