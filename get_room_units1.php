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
    
    // Get bhr_id from request
    $bhrId = isset($_POST['bhr_id']) ? intval($_POST['bhr_id']) : (isset($_GET['bhr_id']) ? intval($_GET['bhr_id']) : 0);
    // Get user_id from request (optional)
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    
    if ($bhrId === 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Room ID (bhr_id) is required.'
        ));
        exit();
    }

    // Get list of room_ids where this user has a PENDING/APPROVED booking
    $userPendingRoomIds = [];
    if ($userId > 0) {
        $pendingSql = "SELECT room_id, booking_status FROM bookings WHERE user_id = ? AND booking_status IN ('Pending', 'Approved')";
        $pendingStmt = $pdo->prepare($pendingSql);
        $pendingStmt->execute([$userId]);
        $userPendingRoomIds = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // First, get room category and capacity to determine if it's Bed Spacer
    $categorySql = "SELECT room_category, capacity FROM boarding_house_rooms WHERE bhr_id = ?";
    $categoryStmt = $pdo->prepare($categorySql);
    $categoryStmt->execute([$bhrId]);
    $roomInfo = $categoryStmt->fetch(PDO::FETCH_ASSOC);
    
    $roomCategory = $roomInfo ? $roomInfo['room_category'] : 'Private Room';
    $capacity = $roomInfo ? intval($roomInfo['capacity']) : 1;
    
    // Fetch ALL room units regardless of status
    // We need to show Occupied units too, based on the user requirement "Occupied - 6/6"
    $sql = "
        SELECT 
            ru.room_id,
            ru.bhr_id,
            ru.room_number,
            ru.status
        FROM room_units ru
        WHERE ru.bhr_id = ?
        ORDER BY ru.room_number ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bhrId]);
    $allUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $units = [];
    foreach ($allUnits as $unit) {
        $roomId = $unit['room_id'];
        $dbStatus = $unit['status']; // 'Available', 'Reserved', 'Occupied', 'Partially Occupied', 'Available(Partially Occupied)'
        
        $displayStatus = $dbStatus; // Default fallback
        $isFull = false;
        
        // Check if THIS user has a pending/approved application for this specific room
        $unit["has_pending_application"] = false;
        $unit["user_application_status"] = null;
        
        // Check manually for this room in the user's applications
        // We could optimize this by fetching status in the initial query too, but loop is fine for small N
        if ($userId > 0) {
            foreach ($userPendingRoomIds as $app) {
                if ($app['room_id'] == $roomId) {
                    $unit["has_pending_application"] = true;
                    $unit["user_application_status"] = $app['booking_status'];
                    break;
                }
            }
        }
        
        if ($roomCategory === 'Bed Spacer') {
            // Count Confirmed bookings (Occupied slots)
            $countConfirmedSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status IN ('Confirmed', 'Active')";
            $countConfirmedStmt = $pdo->prepare($countConfirmedSql);
            $countConfirmedStmt->execute([$roomId]);
            $confirmedCount = intval($countConfirmedStmt->fetchColumn());
            
            // Count Approved bookings (Reserved slots)
            // Note: User query implies "Reserved" means Approved applications
            $countApprovedSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Approved'";
            $countApprovedStmt = $pdo->prepare($countApprovedSql);
            $countApprovedStmt->execute([$roomId]);
            $approvedCount = intval($countApprovedStmt->fetchColumn());

            // Count Pending bookings (Optional: Does pending affect display? User said: "Available - 0/6" if pending)
            // User said: "Reserved is means nga naay na approve na nga applications"
            // So Pending applications do NOT count as reserved/occupied in the display string logic provided.
            
            $totalOccupied = $confirmedCount + $approvedCount; // Total slots taken (physically or reserved)
            
            // Override DB status for Bed Spacer based on counts logic request:
            // "Available" -> No approved, No confirmed (0/6)
            // "Available (X reserved)" -> Has Approved, No Confirmed (or sum < cap)
            // "Available (Partially Occupied)" -> Has Confirmed (and not full)
            // "Occupied" -> Full
            
            if ($totalOccupied >= $capacity) {
                $baseStatus = "Occupied";
                $isFull = true;
                $displayStatus = "Occupied - $totalOccupied/$capacity person(s)";
            } else {
                $isFull = false;
                
                if ($confirmedCount > 0) {
                     // Has confirmed boarders -> Partially Occupied
                     $label = "Available(Partially Occupied)";
                     if ($approvedCount > 0) {
                         // Has separate reserved slots too? Complex string?
                         // User example: "Available (1 reserved) 1/6 person(s)" -> maybe implies 0 occupied, 1 reserved?
                         // If confirmed users exist, usually we just say "Available (Partially Occupied)" or show count.
                         // Let's combine:
                         $label .= " ($approvedCount reserved)";
                     }
                     $displayStatus = "$label - $totalOccupied/$capacity person(s)";
                } elseif ($approvedCount > 0) {
                    // No confirmed, but has reserved
                    $displayStatus = "Available ($approvedCount reserved) - $totalOccupied/$capacity person(s)";
                } else {
                    // Empty
                    $displayStatus = "Available - $totalOccupied/$capacity person(s)";
                }
            }
            
            $unit["total_capacity"] = $capacity;
            $unit["occupied_capacity"] = $totalOccupied; // For UI progress bars
            $unit["pending_capacity"] = $approvedCount; // Treating Approved as the 'Reserved' portion
            $unit["confirmed_capacity"] = $confirmedCount;
            $unit["available_capacity"] = max(0, $capacity - $totalOccupied);
            
        } else {
            // Private Room
            // Logic: Available -> Reserved (Approved) -> Occupied (Confirmed)
            // Check bookings to confirm status if DB status is not synced (robustness)
            
            if ($dbStatus === 'Occupied') {
                $displayStatus = 'Occupied';
                $isFull = true;
            } elseif ($dbStatus === 'Reserved') {
                $displayStatus = 'Reserved'; // Implies Approved application exists
                 // Check if actually has approved booking (double check)
                 $checkResSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Approved'";
                 $checkResStmt = $pdo->prepare($checkResSql);
                 $checkResStmt->execute([$roomId]);
                 if ($checkResStmt->fetchColumn() > 0) {
                     $isFull = true; // Private room reserved = effectively full for others
                 } else {
                     // Maybe expired? If so, should show Available? 
                     // Let's stick to DB status for now, cron job cleans up.
                     $isFull = true;
                 }
            } else {
                 // Check if there are any Approved bookings not reflected in DB status?
                 $checkAppSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Approved'";
                 $checkAppStmt = $pdo->prepare($checkAppSql);
                 $checkAppStmt->execute([$roomId]);
                 if ($checkAppStmt->fetchColumn() > 0) {
                     $displayStatus = 'Reserved';
                     $isFull = true;
                 } else {
                     $displayStatus = 'Available';
                     $isFull = false;
                 }
            }
            
            $unit["total_capacity"] = 0; // Not used for private
        }
        
        $unit["formatted_status"] = $displayStatus; // New field for frontend
        $unit["is_full"] = $isFull;
        
        // Selectable if not full AND user doesn't have pending application here
        // (Actually user CAN have pending, logic allows multiple pending. But logic blocks if *Active* booking exists)
        // For UI: Selectable if Available or Available(...) 
        // Reserved/Occupied = Not Selectable
        $unit["is_selectable"] = !$isFull; 
        
        $units[] = $unit;
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

