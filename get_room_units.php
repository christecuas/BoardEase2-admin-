<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$servername = "localhost";
$username = "boardease";
$password = "boardease";
$database = "boardease2";

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
            "status" => $roomStatus
        ];
        
        // For Bed Spacer, add capacity information and adjust status display
        if ($roomCategory === 'Bed Spacer') {
            // Count Pending bookings (reserved) separately from Confirmed bookings
            $countPendingSql = "SELECT COUNT(*) as pending_count 
                        FROM bookings 
                        WHERE room_id = ? 
                        AND booking_status = 'Pending'";
            $countPendingStmt = $conn->prepare($countPendingSql);
            $countPendingStmt->bind_param("i", $roomId);
            $countPendingStmt->execute();
            $pendingResult = $countPendingStmt->get_result();
            $pendingRow = $pendingResult->fetch_assoc();
            $pendingCount = intval($pendingRow['pending_count']);
            
            // Count Confirmed bookings
            $countConfirmedSql = "SELECT COUNT(*) as confirmed_count 
                        FROM bookings 
                        WHERE room_id = ? 
                        AND booking_status = 'Confirmed'";
            $countConfirmedStmt = $conn->prepare($countConfirmedSql);
            $countConfirmedStmt->bind_param("i", $roomId);
            $countConfirmedStmt->execute();
            $confirmedResult = $countConfirmedStmt->get_result();
            $confirmedRow = $confirmedResult->fetch_assoc();
            $confirmedCount = intval($confirmedRow['confirmed_count']);
            
            // Total occupied = pending + confirmed
            $totalOccupied = $pendingCount + $confirmedCount;
            
            // For Bed Spacer: Use database status, but adjust based on occupancy
            // - If database status is "Occupied": Show "Occupied"
            // - If database status is "Partially Occupied": Show "Partially Occupied" (even if 2/2 full)
            // - If database status is "Available" and totalOccupied == 0: Show "Available"
            // - If database status is "Available" and totalOccupied > 0: Show "Partially Occupied"
            $dbStatus = $roomStatus; // Get the actual database status
            
            if ($dbStatus === 'Occupied') {
                // Database status is "Occupied", show as "Occupied"
                $unitData["status"] = "Occupied";
            } else if ($dbStatus === 'Partially Occupied') {
                // Database status is "Partially Occupied", show as "Partially Occupied" (even if full 2/2)
                $unitData["status"] = "Partially Occupied";
            } else if ($totalOccupied == 0) {
                // No bookings, show as "Available"
                $unitData["status"] = "Available";
            } else {
                // Has bookings but database status is "Available", show as "Partially Occupied"
                $unitData["status"] = "Partially Occupied";
            }
            
            // Add capacity information
            $unitData["total_capacity"] = $capacity;
            $unitData["occupied_capacity"] = $totalOccupied;
            $unitData["pending_capacity"] = $pendingCount; // Reserved (Pending)
            $unitData["confirmed_capacity"] = $confirmedCount; // Confirmed bookings
            $unitData["available_capacity"] = max(0, $capacity - $totalOccupied);
            $unitData["capacity_display"] = $totalOccupied . "/" . $capacity . " person(s)";
        } else {
            // Private Room - no capacity info, use actual status
            $unitData["total_capacity"] = 0;
            $unitData["occupied_capacity"] = 0;
            $unitData["pending_capacity"] = 0;
            $unitData["confirmed_capacity"] = 0;
            $unitData["available_capacity"] = 0;
            $unitData["capacity_display"] = "";
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
