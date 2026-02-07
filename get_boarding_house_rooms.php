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
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get boarding house ID from request
    $bhId = isset($_GET['bh_id']) ? (int)$_GET['bh_id'] : 0;

    if ($bhId === 0) {
        echo json_encode(array('success' => false, 'error' => 'Boarding house ID is required.'));
        exit();
    }

    // Debug: Log the bh_id being queried
    error_log("DEBUG: get_boarding_house_rooms.php - Querying for bh_id: " . $bhId);

    // Fetch room information grouped by category
    $roomDetailsSql = "
        SELECT
            bhr_id,
            room_category,
            room_name,
            price,
            capacity,
            room_description,
            total_rooms,
            created_at
        FROM boarding_house_rooms
        WHERE bh_id = ?
        ORDER BY room_category, price ASC
    ";
    $roomDetailsStmt = $pdo->prepare($roomDetailsSql);
    $roomDetailsStmt->execute([$bhId]);
    $roomDetails = $roomDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the number of rooms found
    error_log("DEBUG: get_boarding_house_rooms.php - Found " . count($roomDetails) . " rooms for bh_id: " . $bhId);
    if (count($roomDetails) > 0) {
        error_log("DEBUG: First room: " . json_encode($roomDetails[0]));
    }

    // Group rooms by category and calculate available rooms for each room
    $groupedRooms = array();
    foreach ($roomDetails as $room) {
        $category = $room['room_category'];
        $bhrId = $room['bhr_id'];
        
        // Count available room units for this bhr_id
        // For Private Room: Count units with status = 'Available'
        // For Bed Spacer: Count units where booking_count < capacity (per unit)
        if ($room['room_category'] === 'Bed Spacer') {
            // For Bed Spacer, count ALL units with status "Available" or "Partially Occupied"
            // Don't exclude based on capacity - show all as long as status is not "Occupied"
            // This ensures "Room(s): 2" shows even if both are "Partially Occupied" (2/2 full)
            $availableRoomsSql = "
                SELECT COUNT(*) as available_count
                FROM room_units ru
                WHERE ru.bhr_id = ? 
                AND ru.status IN ('Available', 'Partially Occupied', 'Available(Partially Occupied)')
            ";
            $availableStmt = $pdo->prepare($availableRoomsSql);
            $availableStmt->execute([$bhrId]);
            $availableResult = $availableStmt->fetch(PDO::FETCH_ASSOC);
            $availableCount = isset($availableResult['available_count']) ? (int)$availableResult['available_count'] : 0;
            
            // Add room information for Bed Spacer
            $room['available_rooms'] = $availableCount;
            
            error_log("DEBUG: Bed Spacer bhr_id=" . $bhrId . " (" . $room['room_name'] . ") - available_rooms: " . $availableCount . " (counted all units with status 'Available', 'Partially Occupied', or 'Available(Partially Occupied)')");
        } else {
            // For Private Room, just count Available status
            $availableRoomsSql = "
                SELECT COUNT(*) as available_count
                FROM room_units
                WHERE bhr_id = ? AND status = 'Available'
            ";
            $availableStmt = $pdo->prepare($availableRoomsSql);
            $availableStmt->execute([$bhrId]);
            $availableResult = $availableStmt->fetch(PDO::FETCH_ASSOC);
            $availableCount = isset($availableResult['available_count']) ? (int)$availableResult['available_count'] : 0;
            
            $room['available_rooms'] = $availableCount;
            error_log("DEBUG: Room bhr_id=" . $bhrId . " (" . $room['room_name'] . ") - available_rooms: " . $room['available_rooms']);
        }
        
        if (!isset($groupedRooms[$category])) {
            $groupedRooms[$category] = array();
        }
        $groupedRooms[$category][] = $room;
    }

    // Format the response
    $response = array(
        'success' => true,
        'data' => array(
            'bh_id' => $bhId,
            'rooms_by_category' => $groupedRooms,
            'total_categories' => count($groupedRooms),
            'total_rooms' => count($roomDetails)
        )
    );

    // Debug: Log the response structure
    error_log("DEBUG: get_boarding_house_rooms.php - Response: " . json_encode($response));

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>