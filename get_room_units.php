<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Connection failed: " . $conn->connect_error]));
}

// Get bhr_id from POST request
$bhr_id = $_POST["bhr_id"] ?? null;

if (!$bhr_id) {
    echo json_encode(["success" => false, "error" => "Room ID is required"]);
    exit;
}

try {
    // First, get room category and capacity to determine if it's Bed Spacer
    $categorySql = "SELECT room_category, capacity FROM boarding_house_rooms WHERE bhr_id = ?";
    $categoryStmt = $conn->prepare($categorySql);
    $categoryStmt->bind_param("i", $bhr_id);
    $categoryStmt->execute();
    $categoryResult = $categoryStmt->get_result();
    $roomInfo = $categoryResult->fetch_assoc();
    
    $roomCategory = $roomInfo ? $roomInfo['room_category'] : 'Private Room';
    $capacity = $roomInfo ? intval($roomInfo['capacity']) : 1;
    
    // Query to get ALL room units (Available, Partially Occupied, and Occupied)
    // For owner's view, show all units regardless of status
    $sql = "SELECT ru.room_id, ru.room_number, ru.status 
            FROM room_units ru
            WHERE ru.bhr_id = ? 
            ORDER BY ru.room_number";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bhr_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $units = [];
    while ($row = $result->fetch_assoc()) {
        $roomId = $row["room_id"];
        $roomStatus = $row["status"];
        $roomNumber = $row["room_number"];
        
        $unitData = [
            "room_id" => $roomId,
            "room_number" => $roomNumber,
            "status" => $roomStatus,
            "is_reserved" => ($roomStatus === 'Reserved' || $roomStatus === 'Partially Occupied'),
            "is_booked" => ($roomStatus === 'Occupied' || $roomStatus === 'Reserved')
        ];
        
        // For Bed Spacer, add capacity information and adjust status display
        if ($roomCategory === 'Bed Spacer') {
            // Count Confirmed bookings (Occupied slots)
            $countConfirmedSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status IN ('Confirmed', 'Active')";
            $countConfirmedStmt = $conn->prepare($countConfirmedSql);
            $countConfirmedStmt->bind_param("i", $roomId);
            $countConfirmedStmt->execute();
            $confirmedResult = $countConfirmedStmt->get_result();
            $confirmedCount = intval($confirmedResult->fetch_row()[0]);
            
            // Count Approved bookings (Reserved slots)
            $countApprovedSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Approved'";
            $countApprovedStmt = $conn->prepare($countApprovedSql);
            $countApprovedStmt->bind_param("i", $roomId);
            $countApprovedStmt->execute();
            $approvedResult = $countApprovedStmt->get_result();
            $approvedCount = intval($approvedResult->fetch_row()[0]);
            
            // Total occupied = pending + confirmed
            $totalOccupied = $confirmedCount + $approvedCount;
            
            // Generate formatted status and capacity strings
            // Goal: "Available (1 reserved) - 1/6 person(s)"
            // Adapter converts to: status + " - " + capacityDisplay
            
            if ($totalOccupied >= $capacity) {
                $status = "Occupied";
            } else {
                if ($confirmedCount > 0) {
                     $status = "Available(Partially Occupied)";
                     if ($approvedCount > 0) {
                         $status .= " ($approvedCount reserved)";
                     }
                } elseif ($approvedCount > 0) {
                    $status = "Available ($approvedCount reserved)";
                } else {
                    $status = "Available";
                }
            }
            
            $unitData["status"] = $status;
            $unitData["capacity_display"] = $totalOccupied . "/" . $capacity . " person(s)";
            
            // Legacy/Flag fields
            $unitData["is_reserved"] = ($approvedCount > 0 || $confirmedCount > 0);
            $unitData["total_capacity"] = $capacity;
            $unitData["occupied_capacity"] = $totalOccupied;
            $unitData["pending_capacity"] = $approvedCount;
            $unitData["confirmed_capacity"] = $confirmedCount;
            $unitData["available_capacity"] = max(0, $capacity - $totalOccupied);
            
        } else {
            // Private Room
            // Logic: Available -> Reserved (Approved) -> Occupied (Confirmed)
            
            $status = $roomStatus; // Default to DB status
            
            // Check real status from bookings if DB is desynced
            if ($status !== 'Occupied') {
                 $checkAppSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Approved'";
                 $checkAppStmt = $conn->prepare($checkAppSql);
                 $checkAppStmt->bind_param("i", $roomId);
                 $checkAppStmt->execute();
                 if ($checkAppStmt->get_result()->fetch_row()[0] > 0) {
                     $status = 'Reserved';
                 } else {
                     $status = 'Available';
                 }
            }
            
            $unitData["status"] = $status;
            $unitData["capacity_display"] = "";
            $unitData["is_reserved"] = ($status === 'Reserved');
            $unitData["total_capacity"] = 0;
            $unitData["occupied_capacity"] = 0;
            $unitData["pending_capacity"] = 0;
            $unitData["confirmed_capacity"] = 0;
            $unitData["available_capacity"] = 0;
            $unitData["available_capacity"] = 0;
        }

        // Check if current user has an application for this room
        $unitData["user_application_status"] = null;
        if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
            $userId = intval($_POST['user_id']);
            
            // First map user_id if needed (similar to other scripts)
            // But android sends the correct user_id usually. 
            // Let's assume the android sends the users.user_id or we check both.
            // Actually, let's just use the ID sent and check against bookings.user_id
            // If the ID sent is from shared prefs, it might be reg_id or user_id. 
            // Let's do a robust check.
            
            // Simplified check: Check matches on user_id directly
            $checkUserAppSql = "SELECT booking_status FROM bookings WHERE room_id = ? AND user_id = ? AND booking_status IN ('Pending', 'Approved') LIMIT 1";
            $checkUserAppStmt = $conn->prepare($checkUserAppSql);
            $checkUserAppStmt->bind_param("ii", $roomId, $userId);
            $checkUserAppStmt->execute();
            $userAppResult = $checkUserAppStmt->get_result();
            if ($rowApp = $userAppResult->fetch_assoc()) {
                $unitData["user_application_status"] = $rowApp['booking_status'];
            }
        }

        
        $units[] = $unitData;
    }

    echo json_encode([
        "success" => true, 
        "units" => $units,
        "total_units" => count($units),
        "room_category" => $roomCategory,
        "capacity" => $capacity
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>
