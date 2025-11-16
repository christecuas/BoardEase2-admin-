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
            error_log("get_boarders_history.php - Got user_id from JSON body: $ownerIdInput");
        }
    }
    
    // Fallback to POST or GET if JSON body didn't have it
    if ($ownerIdInput === 0) {
        $ownerIdInput = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
        if ($ownerIdInput > 0) {
            error_log("get_boarders_history.php - Got user_id from POST/GET: $ownerIdInput");
        }
    }
    
    error_log("get_boarders_history.php - Final owner user_id: $ownerIdInput");
    
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
        error_log("get_boarders_history.php - Using users.user_id: $ownerId");
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
            error_log("get_boarders_history.php - Mapped registrations.id $ownerIdInput to users.user_id: $ownerId");
        } else {
            echo json_encode(array(
                'success' => false,
                'error' => "User ID $ownerIdInput not found in users table."
            ));
            exit();
        }
    }
    
    // Query to get completed bookings (history) for the owner
    // Join bookings with users (boarder), registrations, room_units, boarding_house_rooms, and boarding_houses
    $sql = "
        SELECT 
            CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), ' ', COALESCE(r.suffix, '')) as boarder_name,
            r.email as boarder_email,
            r.phone as boarder_phone,
            ru.room_number,
            b.start_date,
            b.end_date,
            b.booking_status as status,
            bh.bh_name as boarding_house_name,
            bhr.room_category as rent_type,
            u.profile_picture
        FROM bookings b
        INNER JOIN users u ON b.user_id = u.user_id
        INNER JOIN registrations r ON u.reg_id = r.id
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        WHERE bh.user_id = ? 
            AND b.booking_status = 'Completed'
        ORDER BY b.end_date DESC, b.booking_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ownerId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("get_boarders_history.php - Found " . count($results) . " completed bookings for owner_id: $ownerId");
    
    // Format the results
    $boardersHistory = array();
    foreach ($results as $row) {
        $history = array(
            'boarder_name' => trim($row['boarder_name']),
            'boarder_email' => $row['boarder_email'] ? $row['boarder_email'] : '',
            'boarder_phone' => $row['boarder_phone'] ? $row['boarder_phone'] : '',
            'room_number' => $row['room_number'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'status' => $row['status'],
            'boarding_house_name' => $row['boarding_house_name'],
            'rent_type' => $row['rent_type'],
            'profile_picture' => $row['profile_picture'] ? $row['profile_picture'] : ''
        );
        $boardersHistory[] = $history;
    }
    
    echo json_encode(array(
        'success' => true,
        'boarders_history' => $boardersHistory
    ));
    
} catch (PDOException $e) {
    error_log("Database error in get_boarders_history.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Server error in get_boarders_history.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

