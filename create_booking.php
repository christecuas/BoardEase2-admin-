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
        $isStatusValid = (isset($roomUnit['status']) && ($roomUnit['status'] === 'Available' || $roomUnit['status'] === 'Partially Occupied' || $roomUnit['status'] === 'Available(Partially Occupied)'));
    } else {
        // Unknown category, use Private Room logic
        $isStatusValid = (isset($roomUnit['status']) && $roomUnit['status'] === 'Available');
    }
    
    if (!$isStatusValid) {
        error_log("ERROR: Room unit status is not valid for application. Current status: " . ($roomUnit['status'] ?? 'null') . ", category: $roomCategory");
        
        // CRITICAL: Rollback transaction BEFORE exiting
        ob_clean();
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $rollbackError) {
            error_log("ERROR: Failed to rollback transaction: " . $rollbackError->getMessage());
        }
        
        // Return error response
        http_response_code(400);
        $errorResponse = json_encode(array(
            'success' => false,
            'message' => 'Room is not available for rent. Current status: ' . ($roomUnit['status'] ?? 'Unknown')
        ));
        echo $errorResponse;
        ob_end_flush();
        exit; // CRITICAL: Exit immediately
    }
    
    error_log("Step 1 Success: Room unit status is Available, proceeding with application");
    
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
    
    // Check if boarder already has an active booking (Confirmed or Approved)
    // We now allow multiple 'Pending' applications
    error_log("Step 1.5: Checking if boarder already has an active booking (Confirmed/Approved)...");
    $checkActiveBookingSql = "
        SELECT booking_id, booking_status, start_date, end_date
        FROM bookings
        WHERE user_id = :user_id
        AND booking_status IN ('Confirmed', 'Active')
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

    // NEW CHECK: Prevent boarder from applying for the SAME room if they already have a Pending application for it
    // We allow multiple Pending applications, but NOT multiple Pending applications for the SAME room
    error_log("Step 1.7: Checking if boarder already has a PENDING application for this specific room...");
    $checkDuplicatePendingSql = "
        SELECT booking_id, booking_status 
        FROM bookings 
        WHERE user_id = :user_id 
        AND room_id = :room_id 
        AND booking_status IN ('Pending', 'Approved')
        LIMIT 1
    ";
    $checkDuplicatePendingStmt = $pdo->prepare($checkDuplicatePendingSql);
    $checkDuplicatePendingStmt->execute([':user_id' => $actualUserId, ':room_id' => $actualRoomId]);
    
    if ($duplicate = $checkDuplicatePendingStmt->fetch()) {
        error_log("ERROR: Boarder already has a pending/approved application for this room - room_id: $actualRoomId, status: " . $duplicate['booking_status']);
        ob_clean();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        $statusMsg = $duplicate['booking_status'] === 'Approved' ? 'an approved' : 'a pending';
        echo json_encode(array(
            'success' => false,
            'message' => "You already have $statusMsg application for this room. Please proceed with payment or choose another room."
        ));
        ob_end_flush();
        exit;
    }
    error_log("Step 1.7 Success: No duplicate pending application for this room");
    
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
            AND b.booking_status IN ('Confirmed', 'Active')
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
            AND b.booking_status IN ('Confirmed', 'Active')
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
            AND b.booking_status IN ('Confirmed', 'Active')
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
    
    // Step 3: Room status remains 'Available' to allow multiple applications
    error_log("Step 3: Room status remains 'Available' to allow multiple boarders to apply.");
    
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
        error_log("ERROR: Failed to create booking application");
        ob_clean();
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'message' => 'Failed to submit application'
        ));
        ob_end_flush();
        exit;
    }
    
    // Step 5: Skipping payment and breakdown creation at the application stage
    // Advance payment is only required AFTER owner approval
    $paymentId = null;
    error_log("Step 5: Skipping payment record creation (Application stage)");
    
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
        'message' => 'Application submitted successfully',
        'booking_id' => $bookingId
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
