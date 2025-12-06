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
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get bhr_id from request
    $bhrId = isset($_POST['bhr_id']) ? intval($_POST['bhr_id']) : (isset($_GET['bhr_id']) ? intval($_GET['bhr_id']) : 0);
    
    if ($bhrId === 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Room ID (bhr_id) is required.'
        ));
        exit();
    }
    
    // First, get room category and capacity to determine if it's Bed Spacer
    $categorySql = "SELECT room_category, capacity FROM boarding_house_rooms WHERE bhr_id = ?";
    $categoryStmt = $pdo->prepare($categorySql);
    $categoryStmt->execute([$bhrId]);
    $roomInfo = $categoryStmt->fetch(PDO::FETCH_ASSOC);
    
    $roomCategory = $roomInfo ? $roomInfo['room_category'] : 'Private Room';
    $capacity = $roomInfo ? intval($roomInfo['capacity']) : 1;
    
    // Fetch ALL room units (Available and Reserved/Partially Occupied, but exclude Occupied)
    // Status 'Partially Occupied' means room is reserved by a Pending booking
    // Status 'Occupied' means room is fully booked (exclude from list)
    $sql = "
        SELECT 
            ru.room_id,
            ru.bhr_id,
            ru.room_number,
            ru.status
        FROM room_units ru
        WHERE ru.bhr_id = ?
        AND ru.status IN ('Available', 'Partially Occupied')
        ORDER BY ru.room_number ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bhrId]);
    $allUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter units based on room category
    $units = [];
    foreach ($allUnits as $unit) {
        $roomId = $unit['room_id'];
        $roomStatus = $unit['status'];
        $isReserved = ($roomStatus === 'Partially Occupied');
        $isAvailable = true;
        
        // For Bed Spacer, include all units with status "Available" or "Partially Occupied"
        // Even if full (2/2), show it but mark as not selectable
        // Only exclude if status is "Occupied"
        
        // Only add if available or reserved (exclude only "Occupied" status)
        if ($isAvailable || $isReserved) {
            // Add reserved flag
            $unit["is_reserved"] = $isReserved;
            
            // For Bed Spacer, add capacity information
            if ($roomCategory === 'Bed Spacer') {
                // Count Pending bookings (reserved) separately from Confirmed bookings
                $countPendingSql = "
                    SELECT COUNT(*) as pending_count 
                    FROM bookings 
                    WHERE room_id = ? 
                    AND booking_status = 'Pending'
                ";
                $countPendingStmt = $pdo->prepare($countPendingSql);
                $countPendingStmt->execute([$roomId]);
                $pendingResult = $countPendingStmt->fetch(PDO::FETCH_ASSOC);
                $pendingCount = intval($pendingResult['pending_count']);
                
                // Count Confirmed bookings
                $countConfirmedSql = "
                    SELECT COUNT(*) as confirmed_count 
                    FROM bookings 
                    WHERE room_id = ? 
                    AND booking_status = 'Confirmed'
                ";
                $countConfirmedStmt = $pdo->prepare($countConfirmedSql);
                $countConfirmedStmt->execute([$roomId]);
                $confirmedResult = $countConfirmedStmt->fetch(PDO::FETCH_ASSOC);
                $confirmedCount = intval($confirmedResult['confirmed_count']);
                
                // Total occupied = pending + confirmed
                $totalOccupied = $pendingCount + $confirmedCount;
                
                // Check if room is full (not selectable)
                $isFull = ($totalOccupied >= $capacity);
                
                $unit["total_capacity"] = $capacity;
                $unit["occupied_capacity"] = $totalOccupied;
                $unit["pending_capacity"] = $pendingCount; // Reserved (Pending)
                $unit["confirmed_capacity"] = $confirmedCount; // Confirmed bookings
                $unit["available_capacity"] = max(0, $capacity - $totalOccupied);
                $unit["is_full"] = $isFull; // Flag to indicate if room is full (not selectable)
            } else {
                // Private Room - no capacity info
                $unit["total_capacity"] = 0;
                $unit["occupied_capacity"] = 0;
                $unit["available_capacity"] = 0;
            }
            
            $units[] = $unit;
        }
    }
    
    // Format the response
    $response = array(
        'success' => true,
        'units' => $units,
        'room_category' => $roomCategory,
        'capacity' => $capacity
    );
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Server error: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

