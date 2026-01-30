<?php
// Enable error reporting and logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, but log them
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

// Start output buffering to prevent any unwanted output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

// Log that create_booking.php was accessed
error_log("=== CREATE_BOOKING.PHP ACCESSED ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

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
    
    // Start transaction to ensure all operations succeed or fail together
    $pdo->beginTransaction();
    
    // Get POST data - handle both POST and JSON input
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
    
    $roomId = isset($inputData['room_id']) ? intval($inputData['room_id']) : 0;
    $userId = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;
    $startDate = isset($inputData['start_date']) ? trim($inputData['start_date']) : '';
    $endDate = isset($inputData['end_date']) ? trim($inputData['end_date']) : '';
    $paymentMethod = isset($inputData['payment_method']) ? trim($inputData['payment_method']) : 'Cash';
    $paymentProofBase64 = isset($inputData['payment_proof']) ? trim($inputData['payment_proof']) : '';
    
    // Get calculated payment amount and number of days from Android
    // If not provided, calculate it from room price (fallback for backward compatibility)
    $totalAmount = isset($inputData['total_amount']) ? floatval($inputData['total_amount']) : 0;
    $numberOfDays = isset($inputData['number_of_days']) ? intval($inputData['number_of_days']) : 0;
    $paymentBreakdownJson = isset($inputData['payment_breakdown']) ? $inputData['payment_breakdown'] : '';
    
    // Debug logging
    error_log("create_booking.php - Received data: room_id=$roomId, user_id=$userId, start_date=$startDate, end_date=$endDate, total_amount=$totalAmount, number_of_days=$numberOfDays");
    error_log("Payment breakdown JSON length: " . strlen($paymentBreakdownJson));
    
    // Validate required fields
    if ($roomId == 0 || $userId == 0 || empty($startDate) || empty($endDate)) {
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'message' => 'Missing required fields',
            'debug' => array(
                'room_id' => $roomId,
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            )
        ));
        ob_end_flush();
        exit;
    }
    
    // Validate date format
    $startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
    $endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);
    
    if (!$startDateObj || !$endDateObj) {
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'message' => 'Invalid date format. Expected YYYY-MM-DD'
        ));
        ob_end_flush();
        exit;
    }
    
    // Validate end date is after start date
    if ($endDateObj <= $startDateObj) {
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'message' => 'End date must be after start date'
        ));
        ob_end_flush();
        exit;
    }
    
    // Check if room unit exists in room_units table
    // Use SELECT FOR UPDATE to lock the row and prevent concurrent bookings
    // The room_id from Android is now room_units.room_id (selected by user)
    error_log("=== BOOKING PROCESS START ===");
    error_log("Step 1: Checking room unit with room_id: $roomId");
    $checkRoomUnitSql = "SELECT room_id, bhr_id, room_number, status FROM room_units WHERE room_id = :room_id FOR UPDATE";
    $checkRoomUnitStmt = $pdo->prepare($checkRoomUnitSql);
    $checkRoomUnitStmt->execute([':room_id' => $roomId]);
    $roomUnit = $checkRoomUnitStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$roomUnit) {
        error_log("ERROR: Room unit not found for room_id: $roomId");
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("Transaction rolled back - Room unit not found");
        }
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'message' => 'Room unit not found'
        ));
        ob_end_flush();
        exit;
    }
    
    error_log("Step 1 Result: Room unit found - room_id: " . $roomUnit['room_id'] . ", bhr_id: " . $roomUnit['bhr_id'] . ", status: " . $roomUnit['status']);
    
    // Get room category and capacity from boarding_house_rooms (needed for status check)
    $bhrId = $roomUnit['bhr_id'];
    $getRoomInfoSql = "SELECT room_category, capacity FROM boarding_house_rooms WHERE bhr_id = :bhr_id";
    $getRoomInfoStmt = $pdo->prepare($getRoomInfoSql);
    $getRoomInfoStmt->execute([':bhr_id' => $bhrId]);
    $roomInfo = $getRoomInfoStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$roomInfo) {
        error_log("ERROR: Room category not found for bhr_id: $bhrId");
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'message' => 'Room information not found'
        ));
        ob_end_flush();
        exit;
    }
    
    $roomCategory = $roomInfo['room_category'];
    $capacity = intval($roomInfo['capacity']);
    error_log("Step 1.1: Room category: $roomCategory, capacity: $capacity");
    
    // Check if room unit is available
    // For Private Room: Only allow if status is 'Available'
    // For Bed Spacer: Allow if status is 'Available' OR 'Partially Occupied' (capacity will be checked later)
    $isStatusValid = false;
    if ($roomCategory === 'Private Room') {
        $isStatusValid = (isset($roomUnit['status']) && $roomUnit['status'] === 'Available');
    } else if ($roomCategory === 'Bed Spacer') {
        $isStatusValid = (isset($roomUnit['status']) && ($roomUnit['status'] === 'Available' || $roomUnit['status'] === 'Partially Occupied'));
    } else {
        // Unknown category, use Private Room logic
        $isStatusValid = (isset($roomUnit['status']) && $roomUnit['status'] === 'Available');
    }
    
    if (!$isStatusValid) {
        error_log("ERROR: Room unit status is not valid for booking. Current status: " . ($roomUnit['status'] ?? 'null') . ", category: $roomCategory");
        error_log("ERROR: Exiting BEFORE creating booking - no booking should be created");
        
        // CRITICAL: Rollback transaction BEFORE exiting
        ob_clean();
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                error_log("Transaction rolled back successfully - Room not available");
            } else {
                error_log("WARNING: No active transaction to rollback (this is OK if we haven't done any writes yet)");
            }
        } catch (PDOException $rollbackError) {
            error_log("ERROR: Failed to rollback transaction: " . $rollbackError->getMessage());
        }
        
        // Return error response
        http_response_code(400);
        $errorResponse = json_encode(array(
            'success' => false,
            'message' => 'Selected room unit is not available. Status: ' . ($roomUnit['status'] ?? 'Unknown')
        ));
        error_log("ERROR: Returning error response: " . $errorResponse);
        error_log("ERROR: EXITING - No booking should be created after this point");
        echo $errorResponse;
        ob_end_flush();
        exit; // CRITICAL: Exit immediately to prevent any further code execution
    }
    
    error_log("Step 1 Success: Room unit status is valid for booking, proceeding with booking");
    
    // Use room_units.room_id directly (this is what the user selected)
    $actualRoomId = $roomId;
    
    // Check if user exists - userId from Android is users.user_id (from login.php)
    // First try to find by users.user_id (most common case since login.php returns this)
    $checkUserByUserIdSql = "SELECT r.id as reg_id, u.user_id 
                             FROM users u 
                             JOIN registrations r ON u.reg_id = r.id 
                             WHERE u.user_id = :user_id";
    $checkUserByUserIdStmt = $pdo->prepare($checkUserByUserIdSql);
    $checkUserByUserIdStmt->execute([':user_id' => $userId]);
    $user = $checkUserByUserIdStmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found by users.user_id, try to find by registrations.id (fallback)
    if (!$user) {
        $checkUserSql = "SELECT r.id as reg_id, u.user_id 
                         FROM registrations r 
                         LEFT JOIN users u ON r.id = u.reg_id 
                         WHERE r.id = :user_id";
        $checkUserStmt = $pdo->prepare($checkUserSql);
        $checkUserStmt->execute([':user_id' => $userId]);
        $user = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("User not found - searched userId: " . $userId);
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'message' => 'User not found'
        ));
        ob_end_flush();
        exit;
    }
    
    // Get the actual user_id from users table (needed for bookings foreign key)
    $actualUserId = $user['user_id'];
    $regId = $user['reg_id'];
    
    // If user doesn't have a corresponding entry in users table, create one
    if (!$actualUserId) {
        // Insert into users table (without status column if it doesn't exist)
        try {
            $insertUserSql = "INSERT INTO users (reg_id) VALUES (:reg_id)";
            $insertUserStmt = $pdo->prepare($insertUserSql);
            $insertUserStmt->execute([':reg_id' => $regId]);
            $actualUserId = $pdo->lastInsertId();
        } catch (PDOException $e) {
            // If status column exists, try with status
            if (strpos($e->getMessage(), 'status') !== false) {
                $insertUserSql = "INSERT INTO users (reg_id, status) VALUES (:reg_id, 'Active')";
                $insertUserStmt = $pdo->prepare($insertUserSql);
                $insertUserStmt->execute([':reg_id' => $regId]);
                $actualUserId = $pdo->lastInsertId();
            } else {
                throw $e;
            }
        }
        
        if (!$actualUserId) {
            ob_clean();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(array(
                'success' => false,
                'message' => 'Failed to create user entry'
            ));
            ob_end_flush();
            exit;
        }
    }
    
    // NEW CHECK: Prevent boarder from booking multiple rooms
    // Check if boarder already has an active booking (Pending or Confirmed)
    error_log("Step 1.5: Checking if boarder already has an active booking...");
    $checkActiveBookingSql = "
        SELECT booking_id, booking_status, start_date, end_date
        FROM bookings
        WHERE user_id = :user_id
        AND booking_status IN ('Pending', 'Confirmed')
        LIMIT 1
    ";
    $checkActiveBookingStmt = $pdo->prepare($checkActiveBookingSql);
    $checkActiveBookingStmt->execute([':user_id' => $actualUserId]);
    $existingBooking = $checkActiveBookingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingBooking) {
        error_log("ERROR: Boarder already has an active booking - booking_id: " . $existingBooking['booking_id'] . ", status: " . $existingBooking['booking_status']);
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'message' => 'You already have an active booking. Please complete your current stay before booking another room.'
        ));
        ob_end_flush();
        exit;
    }
    error_log("Step 1.5 Success: Boarder has no active bookings, can proceed with new booking");
    
    // Room category and capacity already retrieved in Step 1.1
    error_log("Step 1.6: Room category: $roomCategory, capacity: $capacity");
    
    // Check for overlapping bookings using room_units.room_id (the actual room unit selected)
    // Different logic for Private Room vs Bed Spacer
    error_log("Step 2: Checking for overlapping bookings (room_category: $roomCategory)...");
    
    if ($roomCategory === 'Private Room') {
        // Private Room: Check if ANY overlapping booking exists
        $checkOverlapSql = "
            SELECT b.booking_id 
            FROM bookings b
            INNER JOIN room_units ru ON b.room_id = ru.room_id
            WHERE ru.room_id = :room_id 
            AND b.booking_status IN ('Pending', 'Confirmed')
            AND (
                (b.start_date <= :start_date AND b.end_date >= :start_date)
                OR (b.start_date <= :end_date AND b.end_date >= :end_date)
                OR (b.start_date >= :start_date AND b.end_date <= :end_date)
            )
            LIMIT 1
        ";
        $checkOverlapStmt = $pdo->prepare($checkOverlapSql);
        $checkOverlapStmt->execute([
            ':room_id' => $actualRoomId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        if ($checkOverlapStmt->fetch()) {
            error_log("ERROR: Overlapping booking found for Private Room - room_id: $actualRoomId");
            ob_clean();
            $pdo->rollBack();
            error_log("Transaction rolled back - Overlapping booking");
            http_response_code(400);
            echo json_encode(array(
                'success' => false,
                'message' => 'Room is already booked for the selected dates'
            ));
            ob_end_flush();
            exit;
        }
        error_log("Step 2 Success: No overlapping bookings found for Private Room");
        
    } else if ($roomCategory === 'Bed Spacer') {
        // Bed Spacer: Count overlapping bookings and check against capacity
        $checkOverlapSql = "
            SELECT COUNT(b.booking_id) as overlap_count
            FROM bookings b
            INNER JOIN room_units ru ON b.room_id = ru.room_id
            WHERE ru.room_id = :room_id 
            AND b.booking_status IN ('Pending', 'Confirmed')
            AND (
                (b.start_date <= :start_date AND b.end_date >= :start_date)
                OR (b.start_date <= :end_date AND b.end_date >= :end_date)
                OR (b.start_date >= :start_date AND b.end_date <= :end_date)
            )
        ";
        $checkOverlapStmt = $pdo->prepare($checkOverlapSql);
        $checkOverlapStmt->execute([
            ':room_id' => $actualRoomId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        $overlapResult = $checkOverlapStmt->fetch(PDO::FETCH_ASSOC);
        $overlapCount = intval($overlapResult['overlap_count']);
        error_log("Step 2: Bed Spacer - Found $overlapCount overlapping bookings, capacity: $capacity");
        
        // For Bed Spacer, allow booking if overlap_count < capacity
        if ($overlapCount >= $capacity) {
            error_log("ERROR: Bed Spacer room is at full capacity - room_id: $actualRoomId, overlap_count: $overlapCount, capacity: $capacity");
            ob_clean();
            $pdo->rollBack();
            error_log("Transaction rolled back - Bed Spacer at full capacity");
            http_response_code(400);
            echo json_encode(array(
                'success' => false,
                'message' => 'Room is already fully booked for the selected dates. All ' . $capacity . ' beds are occupied.'
            ));
            ob_end_flush();
            exit;
        }
        error_log("Step 2 Success: Bed Spacer has capacity available ($overlapCount/$capacity beds occupied)");
        
    } else {
        // Unknown room category - use Private Room logic (conservative approach)
        error_log("WARNING: Unknown room category: $roomCategory, using Private Room logic");
        $checkOverlapSql = "
            SELECT b.booking_id 
            FROM bookings b
            INNER JOIN room_units ru ON b.room_id = ru.room_id
            WHERE ru.room_id = :room_id 
            AND b.booking_status IN ('Pending', 'Confirmed')
            AND (
                (b.start_date <= :start_date AND b.end_date >= :start_date)
                OR (b.start_date <= :end_date AND b.end_date >= :end_date)
                OR (b.start_date >= :start_date AND b.end_date <= :end_date)
            )
            LIMIT 1
        ";
        $checkOverlapStmt = $pdo->prepare($checkOverlapSql);
        $checkOverlapStmt->execute([
            ':room_id' => $actualRoomId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        if ($checkOverlapStmt->fetch()) {
            error_log("ERROR: Overlapping booking found - room_id: $actualRoomId");
            ob_clean();
            $pdo->rollBack();
            error_log("Transaction rolled back - Overlapping booking");
            http_response_code(400);
            echo json_encode(array(
                'success' => false,
                'message' => 'Room is already booked for the selected dates'
            ));
            ob_end_flush();
            exit;
        }
        error_log("Step 2 Success: No overlapping bookings found");
    }
    
    // Update room status based on room category
    // Private Room: Set to 'Partially Occupied' (Reserved) when booking is Pending
    // Bed Spacer: Set to 'Partially Occupied' (Reserved) when booking is Pending if capacity would be reached
    // Status will be updated to 'Occupied' when booking is confirmed
    error_log("Step 3: Updating room status (room_category: $roomCategory)...");
    
    if ($roomCategory === 'Private Room') {
        // Private Room: Update status to 'Partially Occupied' (Reserved) BEFORE creating booking to prevent race conditions
        // Use atomic UPDATE that only succeeds if status is still 'Available'
        error_log("Step 3: Attempting to reserve Private Room by updating status to 'Partially Occupied' (Reserved) (atomic operation)...");
        $updateStatusSql = "UPDATE room_units SET status = 'Partially Occupied' WHERE room_id = :room_id AND status = 'Available'";
        $updateStatusStmt = $pdo->prepare($updateStatusSql);
        $updateStatusStmt->execute([':room_id' => $actualRoomId]);
        $rowsAffected = $updateStatusStmt->rowCount();
        error_log("Step 3 Result: Status update attempted - rows affected: $rowsAffected");
        
        // If no rows were affected, room was already booked by another request
        if ($rowsAffected == 0) {
            error_log("ERROR: Failed to reserve Private Room - status was already changed (race condition)");
            // Re-check status to see what it actually is
            $checkStatusSql = "SELECT status FROM room_units WHERE room_id = :room_id";
            $checkStatusStmt = $pdo->prepare($checkStatusSql);
            $checkStatusStmt->execute([':room_id' => $actualRoomId]);
            $actualStatus = $checkStatusStmt->fetch(PDO::FETCH_ASSOC);
            error_log("ERROR: Current room status: " . ($actualStatus['status'] ?? 'null'));
            
            ob_clean();
            $pdo->rollBack();
            error_log("Transaction rolled back - Room reservation failed");
            http_response_code(400);
            echo json_encode(array(
                'success' => false,
                'message' => 'Selected room unit is not available. Status: ' . ($actualStatus['status'] ?? 'Unknown')
            ));
            ob_end_flush();
            exit;
        }
        error_log("Step 3 Success: Private Room reserved (status updated to 'Partially Occupied' - Reserved)");
        
    } else if ($roomCategory === 'Bed Spacer') {
        // Bed Spacer: Check if this booking will fill the capacity (including Pending bookings)
        // Count current active bookings (Pending or Confirmed) including this one we're about to create
        $currentBookingCount = $overlapCount + 1; // +1 for the booking we're about to create
        error_log("Step 3: Bed Spacer - Current bookings after this one: $currentBookingCount/$capacity");
        
        // Get current room status
        $currentStatus = $roomUnit['status'];
        error_log("Step 3: Bed Spacer current status: $currentStatus");
        
        // Only update status if it's 'Available' and needs to be changed to 'Partially Occupied'
        // If it's already 'Partially Occupied', no need to update (capacity check already passed)
        if ($currentStatus === 'Available') {
            // Update from 'Available' to 'Partially Occupied' to reserve the room
            error_log("Step 3: Bed Spacer updating status from 'Available' to 'Partially Occupied'...");
            $updateStatusSql = "UPDATE room_units SET status = 'Partially Occupied' WHERE room_id = :room_id AND status = 'Available'";
            $updateStatusStmt = $pdo->prepare($updateStatusSql);
            $updateStatusStmt->execute([':room_id' => $actualRoomId]);
            $rowsAffected = $updateStatusStmt->rowCount();
            
            if ($rowsAffected == 0) {
                // Room status changed between check and update (race condition)
                error_log("ERROR: Failed to update Bed Spacer status - room was already changed by another request");
                ob_clean();
                $pdo->rollBack();
                error_log("Transaction rolled back - Bed Spacer reservation failed");
                http_response_code(400);
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Selected room unit is not available. It has been reserved by another booking.'
                ));
                ob_end_flush();
                exit;
            }
            error_log("Step 3 Success: Bed Spacer status updated from 'Available' to 'Partially Occupied' (capacity: $currentBookingCount/$capacity)");
        } else if ($currentStatus === 'Partially Occupied') {
            // Already 'Partially Occupied', no need to update - capacity check already passed in Step 2
            error_log("Step 3 Success: Bed Spacer already 'Partially Occupied', proceeding with booking (capacity: $currentBookingCount/$capacity)");
        } else {
            // Unexpected status (should not happen if Step 1.1 check worked)
            error_log("ERROR: Bed Spacer has unexpected status: $currentStatus");
            ob_clean();
            $pdo->rollBack();
            error_log("Transaction rolled back - Bed Spacer unexpected status");
            http_response_code(400);
            echo json_encode(array(
                'success' => false,
                'message' => 'Selected room unit is not available. Status: ' . $currentStatus
            ));
            ob_end_flush();
            exit;
        }
        
    } else {
        // Unknown category - use Private Room logic
        error_log("Step 3: Unknown room category, using Private Room logic...");
        $updateStatusSql = "UPDATE room_units SET status = 'Partially Occupied' WHERE room_id = :room_id AND status = 'Available'";
        $updateStatusStmt = $pdo->prepare($updateStatusSql);
        $updateStatusStmt->execute([':room_id' => $actualRoomId]);
        $rowsAffected = $updateStatusStmt->rowCount();
        error_log("Step 3 Result: Status update attempted - rows affected: $rowsAffected");
    }
    
    // Now create the booking (room is already reserved, so this should always succeed)
    error_log("Step 4: Creating booking - room_id: $actualRoomId, user_id: $actualUserId, start_date: $startDate, end_date: $endDate");
    $insertSql = "
        INSERT INTO bookings (
            room_id, 
            user_id, 
            start_date, 
            end_date, 
            booking_status, 
            booking_date
        ) VALUES (
            :room_id,
            :user_id,
            :start_date,
            :end_date,
            'Pending',
            NOW()
        )
    ";
    
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':room_id' => $actualRoomId,  // This is room_units.room_id
        ':user_id' => $actualUserId,  // Use actual user_id from users table
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);
    
    $bookingId = $pdo->lastInsertId();
    error_log("Step 4 Result: Booking created with booking_id: $bookingId");
    
    if (!$bookingId) {
        error_log("ERROR: Failed to create booking - no booking_id returned");
        // Rollback status update as well
        $rollbackStatusSql = "UPDATE room_units SET status = 'Available' WHERE room_id = :room_id";
        $rollbackStatusStmt = $pdo->prepare($rollbackStatusSql);
        $rollbackStatusStmt->execute([':room_id' => $actualRoomId]);
        error_log("Rolled back room status to 'Available'");
        
        ob_clean();
        $pdo->rollBack();
        error_log("Transaction rolled back - Booking creation failed");
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'message' => 'Failed to create booking'
        ));
        ob_end_flush();
        exit;
    }
    
    // Handle payment proof upload
    $paymentProofPath = '';
    if (!empty($paymentProofBase64)) {
        error_log("Step 5: Processing payment proof upload...");
        try {
            // Remove data URL prefix if present
            $base64Data = $paymentProofBase64;
            if (preg_match('/^data:image\/(\w+);base64,/', $paymentProofBase64, $matches)) {
                $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $paymentProofBase64);
            }
            
            // Decode base64 image
            $imageData = base64_decode($base64Data, true);
            
            if ($imageData === false) {
                error_log("Warning: Failed to decode payment proof base64 data");
            } else {
                // Generate unique filename
                $filename = 'payment_proof_' . $bookingId . '_' . time() . '.jpg';
                $uploadDir = dirname(__DIR__) . '/uploads/payment_proofs/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    error_log("Created payment proof directory: $uploadDir");
                }
                
                $filePath = $uploadDir . $filename;
                
                // Save image
                if (file_put_contents($filePath, $imageData)) {
                    // Store relative path from BoardEase2 directory (for get_payment_proof.php)
                    $paymentProofPath = 'uploads/payment_proofs/' . $filename;
                    error_log("Payment proof saved successfully: $paymentProofPath");
                } else {
                    error_log("Warning: Failed to save payment proof image for booking_id: $bookingId");
                }
            }
        } catch (Exception $e) {
            error_log("Warning: Error processing payment proof: " . $e->getMessage());
            // Continue anyway - booking is still valid
        }
    }
    
    // Get owner_id from room_unit's bhr_id
    // CRITICAL: boarding_houses.user_id stores users.user_id directly (NOT registrations.id)
    // Based on get_boarding_house_details1.php, boarding_houses.user_id = users.user_id
    $bhrId = $roomUnit['bhr_id']; // Get bhr_id from the room_unit we already fetched
    error_log("Step 5: Looking up owner for bhr_id: $bhrId");
    $getOwnerSql = "SELECT 
                        bh.user_id as owner_user_id,
                        u.reg_id as owner_reg_id,
                        u.status as owner_status,
                        r.email as owner_email,
                        r.role as owner_role
                    FROM boarding_house_rooms bhr 
                    JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id 
                    LEFT JOIN users u ON bh.user_id = u.user_id
                    LEFT JOIN registrations r ON u.reg_id = r.id
                    WHERE bhr.bhr_id = :bhr_id";
    $getOwnerStmt = $pdo->prepare($getOwnerSql);
    $getOwnerStmt->execute([':bhr_id' => $bhrId]);
    $ownerData = $getOwnerStmt->fetch(PDO::FETCH_ASSOC);
    
    // boarding_houses.user_id IS users.user_id, so use it directly
    $ownerId = $ownerData ? intval($ownerData['owner_user_id']) : 0;
    $ownerRegId = $ownerData && isset($ownerData['owner_reg_id']) ? intval($ownerData['owner_reg_id']) : 0;
    
    error_log("Step 5 Result: boarding_houses.user_id (owner_user_id)=$ownerId, owner_reg_id=$ownerRegId");
    if ($ownerData) {
        error_log("Step 5 Details: owner_email=" . ($ownerData['owner_email'] ?? 'NULL') . ", owner_role=" . ($ownerData['owner_role'] ?? 'NULL') . ", owner_status=" . ($ownerData['owner_status'] ?? 'NULL'));
    }
    
    // Verify owner exists in users table
    if ($ownerId > 0) {
        error_log("Step 5a: Verifying owner user_id: $ownerId exists in users table");
        $verifyOwnerSql = "SELECT user_id, reg_id, status FROM users WHERE user_id = ?";
        $verifyOwnerStmt = $pdo->prepare($verifyOwnerSql);
        $verifyOwnerStmt->execute([$ownerId]);
        $verifyOwner = $verifyOwnerStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verifyOwner) {
            error_log("Step 5a Success: Owner verified - user_id: $ownerId, reg_id: " . ($verifyOwner['reg_id'] ?? 'NULL') . ", status: " . ($verifyOwner['status'] ?? 'NULL'));
            // Update ownerRegId from verification if it wasn't set
            if (!$ownerRegId && $verifyOwner['reg_id']) {
                $ownerRegId = intval($verifyOwner['reg_id']);
                error_log("Step 5a: Updated owner_reg_id to: $ownerRegId");
            }
        } else {
            error_log("Step 5a ERROR: Owner user_id $ownerId NOT FOUND in users table!");
            error_log("Step 5a ERROR: This should not happen - boarding_houses.user_id should reference existing users.user_id");
            $ownerId = 0; // Reset to 0 so we don't proceed with invalid owner
        }
    } else {
        error_log("ERROR: Owner ID is 0 - boarding_houses.user_id is NULL or invalid for bhr_id: $bhrId");
    }
    
    // Double-check: After transaction, verify owner has user_id (re-query if needed)
    // This is important because the notification will be sent AFTER transaction commit
    
    // Use calculated total amount from Android if provided, otherwise calculate from room price (fallback)
    if ($totalAmount > 0) {
        // Use the calculated amount from Android
        $paymentAmount = $totalAmount;
        error_log("Using calculated total_amount from Android: $paymentAmount");
    } else {
        // Fallback: Get room price for payment amount (using bhr_id from room_unit)
        $getRoomPriceSql = "SELECT price FROM boarding_house_rooms WHERE bhr_id = :bhr_id";
        $getRoomPriceStmt = $pdo->prepare($getRoomPriceSql);
        $getRoomPriceStmt->execute([':bhr_id' => $bhrId]);
        $roomData = $getRoomPriceStmt->fetch(PDO::FETCH_ASSOC);
        $paymentAmount = $roomData ? floatval($roomData['price']) : 0;
        error_log("Using room price as fallback: $paymentAmount");
    }
    
    // Calculate number of days if not provided
    if ($numberOfDays == 0) {
        $numberOfDays = $startDateObj->diff($endDateObj)->days;
        if ($numberOfDays == 0) {
            $numberOfDays = 1; // Minimum 1 day
        }
        error_log("Calculated number_of_days: $numberOfDays");
    }
    
    // Create payment record
    $paymentId = null;
    if ($ownerId > 0) {
        error_log("Step 6: Creating payment record...");
        try {
            $insertPaymentSql = "
                INSERT INTO payments (
                    booking_id,
                    user_id,
                    owner_id,
                    payment_amount,
                    payment_method,
                    payment_proof,
                    payment_status,
                    payment_date
                ) VALUES (
                    :booking_id,
                    :user_id,
                    :owner_id,
                    :payment_amount,
                    :payment_method,
                    :payment_proof,
                    'Pending',
                    NOW()
                )
            ";
            
            $insertPaymentStmt = $pdo->prepare($insertPaymentSql);
            $insertPaymentStmt->execute([
                ':booking_id' => $bookingId,
                ':user_id' => $actualUserId,  // Use actual user_id from users table
                ':owner_id' => $ownerId,
                ':payment_amount' => $paymentAmount,
                ':payment_method' => $paymentMethod,
                ':payment_proof' => $paymentProofPath
            ]);
            $paymentId = $pdo->lastInsertId(); // Get payment_id after insertion
            error_log("Payment record created successfully - payment_id: $paymentId, amount: $paymentAmount");
            
            // Save payment breakdown if provided
            if (!empty($paymentBreakdownJson) && $paymentId) {
                error_log("Step 7: Processing payment breakdown...");
                try {
                    // Parse JSON breakdown
                    $breakdownArray = json_decode($paymentBreakdownJson, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Warning: Invalid payment breakdown JSON - Error: " . json_last_error_msg());
                    } elseif (is_array($breakdownArray) && !empty($breakdownArray)) {
                        error_log("Saving payment breakdown - " . count($breakdownArray) . " periods");
                        
                        // Check if payment_breakdowns table exists by attempting to describe it
                        $tableExists = false;
                        try {
                            $checkTableSql = "DESCRIBE payment_breakdowns";
                            $pdo->query($checkTableSql);
                            $tableExists = true;
                            error_log("payment_breakdowns table exists");
                        } catch (PDOException $e) {
                            error_log("Warning: payment_breakdowns table does not exist or cannot be accessed: " . $e->getMessage());
                            $tableExists = false;
                        }
                        
                        if ($tableExists) {
                            $insertBreakdownSql = "
                                INSERT INTO payment_breakdowns (
                                    booking_id,
                                    payment_id,
                                    period_type,
                                    period_number,
                                    period_label,
                                    period_start_date,
                                    period_end_date,
                                    amount,
                                    is_selected,
                                    payment_status,
                                    due_date
                                ) VALUES (
                                    :booking_id,
                                    :payment_id,
                                    :period_type,
                                    :period_number,
                                    :period_label,
                                    :period_start_date,
                                    :period_end_date,
                                    :amount,
                                    :is_selected,
                                    'Pending',
                                    :due_date
                                )
                            ";
                            
                            $insertBreakdownStmt = $pdo->prepare($insertBreakdownSql);
                            $breakdownCount = 0;
                            
                            foreach ($breakdownArray as $index => $period) {
                                try {
                                    $periodType = isset($period['period_type']) ? trim($period['period_type']) : 'month';
                                    $periodNumber = isset($period['period_number']) ? intval($period['period_number']) : ($index + 1);
                                    $periodLabel = isset($period['label']) ? trim($period['label']) : (isset($period['period_label']) ? trim($period['period_label']) : 'Period ' . ($index + 1));
                                    $periodStartDate = isset($period['start_date']) ? trim($period['start_date']) : $startDate;
                                    $periodEndDate = isset($period['end_date']) ? trim($period['end_date']) : $endDate;
                                    $periodAmount = isset($period['amount']) ? floatval($period['amount']) : 0;
                                    $isSelected = isset($period['is_selected']) ? (bool)$period['is_selected'] : false;
                                    
                                    // Validate period dates
                                    $periodStartObj = DateTime::createFromFormat('Y-m-d', $periodStartDate);
                                    $periodEndObj = DateTime::createFromFormat('Y-m-d', $periodEndDate);
                                    
                                    if (!$periodStartObj || !$periodEndObj) {
                                        error_log("Warning: Invalid date format in breakdown period $index, skipping");
                                        continue;
                                    }
                                    
                                    // Set due date as period start date (can be adjusted later)
                                    $dueDate = $periodStartDate;
                                    
                                    $insertBreakdownStmt->execute([
                                        ':booking_id' => $bookingId,
                                        ':payment_id' => $paymentId,
                                        ':period_type' => $periodType,
                                        ':period_number' => $periodNumber,
                                        ':period_label' => $periodLabel,
                                        ':period_start_date' => $periodStartDate,
                                        ':period_end_date' => $periodEndDate,
                                        ':amount' => $periodAmount,
                                        ':is_selected' => $isSelected ? 1 : 0,
                                        ':due_date' => $dueDate
                                    ]);
                                    
                                    $breakdownCount++;
                                    error_log("Saved breakdown period $index: $periodLabel - Amount: $periodAmount - Selected: " . ($isSelected ? 'Yes' : 'No'));
                                } catch (PDOException $e) {
                                    error_log("Warning: Failed to save breakdown period $index: " . $e->getMessage());
                                    // Continue with next period
                                }
                            }
                            
                            if ($breakdownCount > 0) {
                                error_log("Payment breakdown saved successfully - $breakdownCount periods");
                            } else {
                                error_log("Warning: No breakdown periods were saved");
                            }
                        } else {
                            error_log("Warning: payment_breakdowns table does not exist - skipping breakdown save");
                        }
                    } else {
                        error_log("Warning: Payment breakdown JSON is empty or not an array");
                    }
                } catch (Exception $e) {
                    error_log("Warning: Error processing payment breakdown: " . $e->getMessage());
                    error_log("Breakdown error trace: " . $e->getTraceAsString());
                    // Continue anyway - booking and payment are still valid
                }
            } else {
                if (empty($paymentBreakdownJson)) {
                    error_log("No payment breakdown JSON provided");
                } else {
                    error_log("Warning: Payment ID is null, cannot save breakdown");
                }
            }
        } catch (PDOException $e) {
            // Log error but don't fail if payment creation fails
            error_log("Warning: Could not create payment record: " . $e->getMessage());
            error_log("Payment error trace: " . $e->getTraceAsString());
            // Continue anyway - booking is still valid
        }
    } else {
        error_log("Warning: Owner ID is 0 or not found - skipping payment creation");
    }
    
    // Commit transaction - all operations succeeded
    error_log("Step 8: Committing transaction for booking_id: $bookingId");
    $pdo->commit();
    error_log("Step 8 Success: Transaction committed successfully");
    
    // Send notification to owner about new payment (if payment was created)
    if ($paymentId && $ownerId > 0) {
        try {
            require_once 'activity_notifications.php';
            
            // Get payment and booking details for notification
            $getPaymentDetailsSql = "
                SELECT 
                    p.payment_amount,
                    bhr.room_name,
                    bh.bh_name
                FROM payments p
                JOIN bookings b ON p.booking_id = b.booking_id
                JOIN room_units ru ON b.room_id = ru.room_id
                JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE p.payment_id = ?
            ";
            $getPaymentDetailsStmt = $pdo->prepare($getPaymentDetailsSql);
            $getPaymentDetailsStmt->execute([$paymentId]);
            $paymentDetails = $getPaymentDetailsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($paymentDetails) {
                ActivityNotifications::notifyPaymentCreated($ownerId, [
                    'amount' => floatval($paymentDetails['payment_amount']),
                    'description' => 'Payment for ' . ($paymentDetails['room_name'] ?? 'room') . ' at ' . ($paymentDetails['bh_name'] ?? 'boarding house'),
                    'payment_id' => $paymentId,
                    'booking_id' => $bookingId
                ]);
                error_log("Notification sent to owner (user_id: $ownerId) about new payment pending");
            }
        } catch (Exception $e) {
            error_log("Warning: Failed to send payment creation notification: " . $e->getMessage());
        }
    }
    
    // Send notification to owner about new booking
    error_log("============================================");
    error_log("BOOKING NOTIFICATION PROCESS START");
    error_log("============================================");
    error_log("Step 9: Sending notification to owner about new booking...");
    error_log("Step 9-0: Booking ID: $bookingId, Owner ID: $ownerId, Owner Reg ID: " . ($ownerRegId ?? 'not set'));
    
    try {
        // Check if activity_notifications.php exists
        if (!file_exists('activity_notifications.php')) {
            error_log("Step 9 ERROR: activity_notifications.php file not found!");
            throw new Exception("activity_notifications.php file not found");
        }
        
        require_once 'activity_notifications.php';
        error_log("Step 9-1: Successfully loaded activity_notifications.php");
        
        // Get boarder name and room name for notification
        error_log("Step 9-2: Querying booking details for booking_id: $bookingId");
        $getBookingDetailsSql = "
            SELECT 
                CONCAT(r.first_name, ' ', r.last_name) as boarder_name,
                bhr.room_name,
                bh.bh_name,
                b.user_id as boarder_user_id,
                bh.user_id as owner_user_id_from_bh
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            JOIN registrations r ON u.reg_id = r.id
            JOIN room_units ru ON b.room_id = ru.room_id
            JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE b.booking_id = ?
        ";
        $getBookingDetailsStmt = $pdo->prepare($getBookingDetailsSql);
        $getBookingDetailsStmt->execute([$bookingId]);
        $bookingDetails = $getBookingDetailsStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bookingDetails) {
            error_log("Step 9-2 ERROR: Booking details query returned no results for booking_id: $bookingId");
        } else {
            error_log("Step 9-2 Success: Booking details retrieved");
            error_log("Step 9-2a: boarder_name: " . ($bookingDetails['boarder_name'] ?? 'null'));
            error_log("Step 9-2b: room_name: " . ($bookingDetails['room_name'] ?? 'null'));
            error_log("Step 9-2c: bh_name: " . ($bookingDetails['bh_name'] ?? 'null'));
            error_log("Step 9-2d: boarder_user_id: " . ($bookingDetails['boarder_user_id'] ?? 'null'));
            error_log("Step 9-2e: owner_user_id_from_bh: " . ($bookingDetails['owner_user_id_from_bh'] ?? 'null'));
        }
        
        error_log("Step 9-3: Owner ID check - ownerId: $ownerId, ownerRegId: " . ($ownerRegId ?? 'not set'));
        
        // CRITICAL: Re-verify owner user_id AFTER transaction commit
        // boarding_houses.user_id IS users.user_id, so we should use it directly
        // If ownerId is still 0, try to get it from booking details (which gets it from boarding_houses)
        if ($ownerId <= 0 && isset($bookingDetails['owner_user_id_from_bh']) && $bookingDetails['owner_user_id_from_bh'] > 0) {
            error_log("Step 9-3a: Owner user_id is 0, using owner_user_id_from_bh: " . $bookingDetails['owner_user_id_from_bh']);
            $ownerId = intval($bookingDetails['owner_user_id_from_bh']);
            error_log("Step 9-3a Success: Using owner_user_id from boarding_houses: $ownerId");
            
            // Verify this user_id exists in users table
            $verifyOwnerSql = "SELECT user_id, reg_id FROM users WHERE user_id = ?";
            $verifyOwnerStmt = $pdo->prepare($verifyOwnerSql);
            $verifyOwnerStmt->execute([$ownerId]);
            $verifyOwner = $verifyOwnerStmt->fetch(PDO::FETCH_ASSOC);
            if ($verifyOwner) {
                error_log("Step 9-3a Verification: Owner user_id $ownerId exists, reg_id: " . ($verifyOwner['reg_id'] ?? 'NULL'));
                if (!$ownerRegId && $verifyOwner['reg_id']) {
                    $ownerRegId = intval($verifyOwner['reg_id']);
                    error_log("Step 9-3a: Updated owner_reg_id to: $ownerRegId");
                }
            } else {
                error_log("Step 9-3a ERROR: Owner user_id $ownerId from boarding_houses does NOT exist in users table!");
            }
        } elseif ($ownerId <= 0) {
            error_log("Step 9-3 ERROR: Owner user_id is still 0 after all attempts");
        }
        
        // Final verification: Check if owner exists in users table
        if ($ownerId > 0) {
            error_log("Step 9-4: Verifying owner user_id: $ownerId exists in users table");
            $verifyOwnerSql = "SELECT u.user_id, u.reg_id, r.email, r.first_name, r.last_name, r.role FROM users u JOIN registrations r ON u.reg_id = r.id WHERE u.user_id = ?";
            $verifyOwnerStmt = $pdo->prepare($verifyOwnerSql);
            $verifyOwnerStmt->execute([$ownerId]);
            $verifyOwner = $verifyOwnerStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verifyOwner) {
                error_log("Step 9-4 Success: Owner verified - user_id: " . $verifyOwner['user_id'] . ", email: " . $verifyOwner['email'] . ", role: " . $verifyOwner['role']);
            } else {
                error_log("Step 9-4 ERROR: Owner user_id $ownerId NOT FOUND in users table!");
                $ownerId = 0; // Reset to 0 so we don't proceed
            }
        }
        
        if ($bookingDetails && $ownerId > 0) {
            error_log("Step 9-5: All checks passed, calling ActivityNotifications::notifyBookingCreated");
            error_log("Step 9-5a: Parameters - owner_id: $ownerId");
            error_log("Step 9-5b: Parameters - boarder_name: " . ($bookingDetails['boarder_name'] ?? 'null'));
            error_log("Step 9-5c: Parameters - room_name: " . ($bookingDetails['room_name'] ?? 'null'));
            error_log("Step 9-5d: Parameters - booking_id: $bookingId");
            error_log("Step 9-5e: Parameters - bh_name: " . ($bookingDetails['bh_name'] ?? 'null'));
            
            // CRITICAL: Verify the ownerId matches what login.php would return
            $verifyLoginUserIdSql = "
                SELECT r.id as reg_id, u.user_id 
                FROM registrations r 
                LEFT JOIN users u ON r.id = u.reg_id 
                WHERE u.user_id = ?
            ";
            $verifyLoginUserIdStmt = $pdo->prepare($verifyLoginUserIdSql);
            $verifyLoginUserIdStmt->execute([$ownerId]);
            $verifyLoginUser = $verifyLoginUserIdStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verifyLoginUser) {
                $loginUserId = $verifyLoginUser['user_id'] ? $verifyLoginUser['user_id'] : $verifyLoginUser['reg_id'];
                error_log("Step 9-5f: Login.php would return user_id: $loginUserId");
                
                if ($loginUserId != $ownerId) {
                    error_log("Step 9-5f WARNING: Mismatch! Using ownerId=$ownerId but login would return $loginUserId");
                    $ownerId = intval($loginUserId);
                    error_log("Step 9-5f: Corrected ownerId to: $ownerId");
                } else {
                    error_log("Step 9-5f: Owner user_id matches login user_id - OK");
                }
            } else {
                error_log("Step 9-5f ERROR: Could not verify login user_id for owner_id: $ownerId");
            }
            
            error_log("Step 9-6: Calling ActivityNotifications::notifyBookingCreated()...");
            $notificationResult = ActivityNotifications::notifyBookingCreated($ownerId, [
                'boarder_name' => $bookingDetails['boarder_name'],
                'room_name' => $bookingDetails['room_name'],
                'booking_id' => $bookingId,
                'bh_name' => $bookingDetails['bh_name']
            ]);
            
            error_log("Step 9-7: Notification result received");
            error_log("Step 9-7a: Result type: " . gettype($notificationResult));
            error_log("Step 9-7b: Result: " . json_encode($notificationResult, JSON_UNESCAPED_SLASHES));
            
            if ($notificationResult && isset($notificationResult['success']) && $notificationResult['success']) {
                error_log("Step 9-8: Notification result indicates SUCCESS");
                error_log("Step 9-8a: Notification sent successfully to owner (user_id: $ownerId) about new booking");
                
                // Verify notification was created in database
                error_log("Step 9-9: Verifying notification in database...");
                $verifyNotifSql = "SELECT notif_id, user_id, notif_title, notif_message, notif_type, notif_created_at FROM notifications WHERE user_id = ? AND notif_type = 'booking' ORDER BY notif_id DESC LIMIT 1";
                $verifyNotifStmt = $pdo->prepare($verifyNotifSql);
                $verifyNotifStmt->execute([$ownerId]);
                $verifyNotif = $verifyNotifStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($verifyNotif) {
                    error_log("Step 9-9 SUCCESS: Notification verified in database");
                    error_log("Step 9-9a: notif_id: " . $verifyNotif['notif_id']);
                    error_log("Step 9-9b: user_id: " . $verifyNotif['user_id']);
                    error_log("Step 9-9c: notif_title: " . $verifyNotif['notif_title']);
                    error_log("Step 9-9d: notif_message: " . $verifyNotif['notif_message']);
                    error_log("Step 9-9e: notif_type: " . $verifyNotif['notif_type']);
                    error_log("Step 9-9f: notif_created_at: " . $verifyNotif['notif_created_at']);
                    
                    // Check if owner can see this notification
                    $checkOwnerNotifSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND notif_type = 'booking'";
                    $checkOwnerNotifStmt = $pdo->prepare($checkOwnerNotifSql);
                    $checkOwnerNotifStmt->execute([$ownerId]);
                    $notifCount = $checkOwnerNotifStmt->fetch(PDO::FETCH_ASSOC)['count'];
                    error_log("Step 9-9g: Total booking notifications for owner user_id $ownerId: $notifCount");
                } else {
                    error_log("Step 9-9 ERROR: Notification was NOT found in database after creation attempt");
                    error_log("Step 9-9 ERROR: Searched for user_id: $ownerId, notif_type: 'booking'");
                }
            } else {
                error_log("Step 9-8 ERROR: Notification result indicates FAILURE");
                error_log("Step 9-8a: Result: " . json_encode($notificationResult, JSON_UNESCAPED_SLASHES));
                
                // Try to find the owner's user_id again in case it wasn't resolved correctly
                if (isset($ownerRegId) && $ownerRegId > 0) {
                    error_log("Step 9-10: Retrying with owner reg_id: $ownerRegId");
                    $findOwnerSql = "SELECT user_id FROM users WHERE reg_id = ?";
                    $findOwnerStmt = $pdo->prepare($findOwnerSql);
                    $findOwnerStmt->execute([$ownerRegId]);
                    $foundOwner = $findOwnerStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($foundOwner && $foundOwner['user_id']) {
                        error_log("Step 9-10a: Found owner user_id: " . $foundOwner['user_id'] . " - retrying notification");
                        $retryResult = ActivityNotifications::notifyBookingCreated($foundOwner['user_id'], [
                            'boarder_name' => $bookingDetails['boarder_name'],
                            'room_name' => $bookingDetails['room_name'],
                            'booking_id' => $bookingId,
                            'bh_name' => $bookingDetails['bh_name']
                        ]);
                        error_log("Step 9-10b: Retry result: " . json_encode($retryResult, JSON_UNESCAPED_SLASHES));
                    } else {
                        error_log("Step 9-10 ERROR: Could not find owner user_id from reg_id: $ownerRegId");
                    }
                }
            }
        } else {
            error_log("Step 9-11 ERROR: Cannot proceed with notification");
            if (!$bookingDetails) {
                error_log("Step 9-11a ERROR: Booking details not found for booking_id: $bookingId");
            }
            if ($ownerId <= 0) {
                error_log("Step 9-11b ERROR: Owner ID is invalid ($ownerId) - cannot send notification");
                error_log("Step 9-11b ERROR: Owner reg_id was: " . ($ownerRegId ?? 'not set'));
                
                // Try to get owner from boarding house (bh.user_id IS users.user_id)
                if (isset($bookingDetails['owner_user_id_from_bh']) && $bookingDetails['owner_user_id_from_bh'] > 0) {
                    error_log("Step 9-11c: Attempting to get owner from bh.user_id: " . $bookingDetails['owner_user_id_from_bh']);
                    // boarding_houses.user_id IS users.user_id, so use it directly
                    $finalOwnerId = intval($bookingDetails['owner_user_id_from_bh']);
                    $finalOwnerSql = "SELECT u.user_id, u.reg_id FROM users u WHERE u.user_id = ?";
                    $finalOwnerStmt = $pdo->prepare($finalOwnerSql);
                    $finalOwnerStmt->execute([$finalOwnerId]);
                    $finalOwner = $finalOwnerStmt->fetch(PDO::FETCH_ASSOC);
                    if ($finalOwner && $finalOwner['user_id']) {
                        error_log("Step 9-11c SUCCESS: Found owner user_id: " . $finalOwner['user_id'] . ", reg_id: " . ($finalOwner['reg_id'] ?? 'NULL'));
                        error_log("Step 9-11c: Attempting notification with this user_id");
                        try {
                            $finalResult = ActivityNotifications::notifyBookingCreated($finalOwner['user_id'], [
                                'boarder_name' => $bookingDetails['boarder_name'],
                                'room_name' => $bookingDetails['room_name'],
                                'booking_id' => $bookingId,
                                'bh_name' => $bookingDetails['bh_name']
                            ]);
                            error_log("Step 9-11c Result: " . json_encode($finalResult, JSON_UNESCAPED_SLASHES));
                        } catch (Exception $finalEx) {
                            error_log("Step 9-11c Exception: " . $finalEx->getMessage());
                        }
                    } else {
                        error_log("Step 9-11c ERROR: Could not find owner user_id " . $finalOwnerId . " in users table");
                    }
                }
            }
        }
        
        error_log("============================================");
        error_log("BOOKING NOTIFICATION PROCESS END");
        error_log("============================================");
        
    } catch (Exception $e) {
        // Don't fail the booking if notification fails
        error_log("============================================");
        error_log("BOOKING NOTIFICATION PROCESS EXCEPTION");
        error_log("============================================");
        error_log("Step 9 EXCEPTION: Exception while sending booking notification");
        error_log("Step 9 EXCEPTION Message: " . $e->getMessage());
        error_log("Step 9 EXCEPTION File: " . $e->getFile());
        error_log("Step 9 EXCEPTION Line: " . $e->getLine());
        error_log("Step 9 EXCEPTION Trace: " . $e->getTraceAsString());
        error_log("============================================");
    }
    
    error_log("=== BOOKING PROCESS COMPLETE - SUCCESS ===");
    error_log("Final result: booking_id=$bookingId, room_id=$actualRoomId, user_id=$actualUserId, payment_id=" . ($paymentId ?? 'null'));
    
    ob_clean();
    http_response_code(200);
    echo json_encode(array(
        'success' => true,
        'message' => 'Booking created successfully',
        'booking_id' => $bookingId,
        'payment_id' => $paymentId
    ));
    ob_end_flush();
    
} catch (PDOException $e) {
    // Rollback transaction on error
    error_log("=== BOOKING PROCESS FAILED - PDOException ===");
    error_log("Exception message: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    ob_clean();
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        error_log("Transaction rolled back due to PDOException");
    } else {
        error_log("WARNING: No active transaction to rollback");
    }
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
    ob_end_flush();
} catch (Exception $e) {
    // Rollback transaction on error
    error_log("=== BOOKING PROCESS FAILED - Exception ===");
    error_log("Exception message: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    ob_clean();
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        error_log("Transaction rolled back due to Exception");
    } else {
        error_log("WARNING: No active transaction to rollback");
    }
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ));
    ob_end_flush();
}
?>
