<?php
// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit();
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
    
    // Get user_id (owner's user_id) from JSON body, POST, or GET request
    $ownerIdInput = 0;
    
    // Try to read from JSON body first (for Android app)
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
        if ($data && isset($data['user_id'])) {
            $ownerIdInput = intval($data['user_id']);
            error_log("get_current_boarders.php - Got user_id from JSON body: $ownerIdInput");
        }
    }
    
    // Fallback to POST or GET if JSON body didn't have it
    if ($ownerIdInput === 0) {
        $ownerIdInput = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
        if ($ownerIdInput > 0) {
            error_log("get_current_boarders.php - Got user_id from POST/GET: $ownerIdInput");
        }
    }
    
    error_log("get_current_boarders.php - Final owner user_id: $ownerIdInput");
    
    if ($ownerIdInput === 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'User ID is required.'
        ));
        exit();
    }
    
    // Map user_id to users.user_id if needed
    $ownerId = null;
    
    // Check if it's a users.user_id
    $checkUserSql = "SELECT user_id FROM users WHERE user_id = ?";
    $checkUserStmt = $pdo->prepare($checkUserSql);
    $checkUserStmt->execute([$ownerIdInput]);
    $userRow = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userRow) {
        $ownerId = $ownerIdInput;
        error_log("get_current_boarders.php - Using users.user_id: $ownerId");
    } else {
        // Check if it's a registrations.id and try to map
        $checkRegSql = "SELECT u.user_id FROM registrations r 
                       LEFT JOIN users u ON r.id = u.reg_id 
                       WHERE r.id = ?";
        $checkRegStmt = $pdo->prepare($checkRegSql);
        $checkRegStmt->execute([$ownerIdInput]);
        $regRow = $checkRegStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($regRow && $regRow['user_id']) {
            $ownerId = $regRow['user_id'];
            error_log("get_current_boarders.php - Mapped registrations.id $ownerIdInput to users.user_id: $ownerId");
        } else {
            echo json_encode(array(
                'success' => false,
                'error' => "User ID $ownerIdInput not found in users table."
            ));
            exit();
        }
    }
    
    // Query to get current active boarders for the owner
    // Join active_boarders with users (boarder), registrations, room_units, boarding_house_rooms, and boarding_houses
    // Get booking dates directly from bookings table (most recent booking for each active boarder)
    // First try to match by user_id AND room_id, if no match, try to get most recent booking for user_id
    $sql = "
        SELECT 
            ab.active_id as boarder_id,
            CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), ' ', COALESCE(r.suffix, '')) as boarder_name,
            r.email as boarder_email,
            r.phone as boarder_phone,
            bh.bh_name as boarding_house_name,
            ru.room_number,
            bhr.room_category as rent_type,
            COALESCE(b_exact.start_date, b_user.start_date, '') as start_date,
            COALESCE(b_exact.end_date, b_user.end_date, '') as end_date,
            ab.status,
            u.profile_picture
        FROM active_boarders ab
        INNER JOIN users u ON ab.user_id = u.user_id
        INNER JOIN registrations r ON u.reg_id = r.id
        INNER JOIN room_units ru ON ab.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON ab.boarding_house_id = bh.bh_id
        LEFT JOIN bookings b_exact ON ab.user_id = b_exact.user_id 
            AND ab.room_id = b_exact.room_id 
            AND b_exact.booking_status != 'Cancelled'
            AND b_exact.booking_status != 'Completed'
            AND b_exact.booking_id = (
                SELECT MAX(b2.booking_id)
                FROM bookings b2
                WHERE b2.user_id = ab.user_id
                    AND b2.room_id = ab.room_id
                    AND b2.booking_status != 'Cancelled'
                    AND b2.booking_status != 'Completed'
            )
        LEFT JOIN bookings b_user ON ab.user_id = b_user.user_id 
            AND b_user.booking_status != 'Cancelled'
            AND b_user.booking_status != 'Completed'
            AND b_user.booking_id = (
                SELECT MAX(b3.booking_id)
                FROM bookings b3
                WHERE b3.user_id = ab.user_id
                    AND b3.booking_status != 'Cancelled'
                    AND b3.booking_status != 'Completed'
            )
        WHERE bh.user_id = ? 
            AND ab.status = 'Active'
            AND (COALESCE(b_exact.end_date, b_user.end_date, '') = '' 
                 OR DATE(COALESCE(b_exact.end_date, b_user.end_date, '')) >= CURDATE())
            AND COALESCE(b_exact.booking_status, b_user.booking_status, '') != 'Completed'
        ORDER BY ab.active_id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ownerId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("get_current_boarders.php - Found " . count($results) . " active boarders for owner_id: $ownerId");
    
    // Debug: Check what bookings exist for debugging
    $debugSql = "SELECT booking_id, user_id, room_id, start_date, end_date, booking_status FROM bookings WHERE booking_status != 'Cancelled' ORDER BY booking_id DESC LIMIT 10";
    $debugStmt = $pdo->query($debugSql);
    $debugBookings = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("get_current_boarders.php - Sample bookings (non-cancelled): " . json_encode($debugBookings));
    
    // Get base URL for images
    $baseUrl = 'http://192.168.1.4/boardease_v3/';
    
    // Format the results
    $boarders = array();
    foreach ($results as $row) {
        // Get dates from bookings table - check if they exist and are not empty
        $startDate = '';
        $endDate = '';
        
        if (isset($row['start_date']) && $row['start_date'] !== null && $row['start_date'] !== '') {
            $startDate = $row['start_date'];
        }
        if (isset($row['end_date']) && $row['end_date'] !== null && $row['end_date'] !== '') {
            $endDate = $row['end_date'];
        }
        
        // Log dates for debugging
        error_log("Boarder: " . trim($row['boarder_name']) . " (user_id from active_boarders: " . $row['boarder_id'] . ") - start_date: " . ($startDate ?: 'EMPTY') . ", end_date: " . ($endDate ?: 'EMPTY'));
        error_log("Raw row data - start_date: " . var_export($row['start_date'], true) . ", end_date: " . var_export($row['end_date'], true));
        
        $boarder = array(
            'boarder_id' => (int)$row['boarder_id'],
            'boarder_name' => trim($row['boarder_name']),
            'boarder_email' => $row['boarder_email'],
            'boarder_phone' => $row['boarder_phone'] ? $row['boarder_phone'] : '',
            'boarding_house_name' => $row['boarding_house_name'],
            'room_number' => $row['room_number'],
            'rent_type' => $row['rent_type'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $row['status'],
            'profile_picture' => $row['profile_picture'] ? $row['profile_picture'] : ''
        );
        $boarders[] = $boarder;
    }
    
    echo json_encode(array(
        'success' => true,
        'boarders' => $boarders
    ));
    
} catch (PDOException $e) {
    error_log("Database error in get_current_boarders.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Server error in get_current_boarders.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

