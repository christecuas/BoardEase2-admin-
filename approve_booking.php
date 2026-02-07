<?php
// Enable error reporting and logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Ensure script continues running after response is sent
ignore_user_abort(true);
set_time_limit(0);

// Start output buffering early to catch any output
ob_start();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    ob_end_flush();
    exit;
}

// Set headers first, before any output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept, Authorization');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get POST data
    $json = file_get_contents('php://input');
    if (function_exists('error_log')) {
        error_log("approve_booking.php - Received JSON: " . $json);
    }
    
    $data = json_decode($json, true);
    
    if ($data === null) {
        if (function_exists('error_log')) {
            error_log("approve_booking.php - JSON decode failed: " . json_last_error_msg());
        }
        ob_clean();
        echo json_encode(array(
            'success' => false,
            'error' => 'Invalid JSON data: ' . json_last_error_msg()
        ));
        ob_end_flush();
        exit;
    }
    
    if (function_exists('error_log')) {
        error_log("approve_booking.php - Decoded data: " . print_r($data, true));
    }
    
    $bookingId = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
    $ownerId = isset($data['owner_id']) ? intval($data['owner_id']) : 0;
    
    if (function_exists('error_log')) {
        error_log("approve_booking.php - booking_id: $bookingId, owner_id: $ownerId");
    }
    
    if ($bookingId == 0 || $ownerId == 0) {
        ob_clean();
        echo json_encode(array(
            'success' => false,
            'error' => 'Booking ID and Owner ID are required'
        ));
        ob_end_flush();
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify that the booking exists and belongs to the owner
    // Also get booking details needed for active_boarders insertion and room status update
    // boarding_houses.user_id refers to users.user_id directly
    // Use FOR UPDATE to lock the row and prevent double-processing
    $verifySql = "
        SELECT 
            b.booking_id, 
            b.booking_status, 
            b.user_id as boarder_user_id,
            b.room_id,
            b.start_date,
            b.end_date,
            bh.user_id as owner_user_id,
            bh.bh_id as boarding_house_id,
            bhr.room_category,
            bhr.capacity,
            bhr.price
        FROM bookings b
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        WHERE b.booking_id = :booking_id
        FOR UPDATE
    ";
    
    $verifyStmt = $pdo->prepare($verifySql);
    $verifyStmt->execute([':booking_id' => $bookingId]);
    $booking = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $pdo->rollBack();
        ob_clean();
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'success' => false,
            'error' => 'Booking not found',
            'booking_id' => $bookingId
        ), JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    }
    
    // Verify owner - boarding_houses.user_id is users.user_id
    if ($booking['owner_user_id'] != $ownerId) {
        $pdo->rollBack();
        ob_clean();
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'success' => false,
            'error' => 'Unauthorized: This booking does not belong to you',
            'booking_id' => $bookingId
        ), JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    }
    
    // Check if booking is already processed - prevent double-processing
    if ($booking['booking_status'] != 'Pending' && $booking['booking_status'] != 'Approved') {
        $pdo->rollBack();
        ob_clean();
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        // If booking is already Confirmed, return success
        if ($booking['booking_status'] == 'Confirmed') {
            echo json_encode(array(
                'success' => true,
                'message' => 'Booking is already Confirmed',
                'status' => 'Confirmed',
                'booking_id' => $bookingId
            ), JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(array(
                'success' => false,
                'error' => 'Booking status is ' . $booking['booking_status'] . ' and cannot be approved.',
                'booking_id' => $bookingId
            ), JSON_UNESCAPED_SLASHES);
        }
        ob_end_flush();
        exit;
    }
    
    $currentStatus = $booking['booking_status'];
    $newStatus = '';
    $newRoomStatus = '';
    $isFinalConfirmation = false;
    
    if ($currentStatus == 'Pending') {
        // Stage 2: Initial Approval
        $newStatus = 'Approved';
        
        // Check Room Category for specific status update
        if ($booking['room_category'] === 'Private Room') {
            $newRoomStatus = 'Reserved'; 
        } else {
            // For Bed Spacer, status might remain 'Available' or 'Partially Occupied' depending on logic
            // But usually we don't change room status to 'Reserved' for Bed Spacer until we count seats
            // Let's keep it 'Available' or existing status for now, logic in get_room_units1.php handles display
            // However, the prompt implies "Reserved" is a status for the unit too?
            // "Available (1 reserved)" suggests the status string is complex, or calculated.
            // If Bed Spacer unit status is ENUM, we might not change it here unless it becomes FULL.
            // So for Bed Spacer, we might NOT update room_units.status here, only for Private.
            $newRoomStatus = null; // Do not update specifically yet, or keep current
        }
        
        $message = 'Booking application approved. Waiting for boarder payment.';
        $notificationAction = 'Approved';
        
        // Set approval_date for 24h expiration timer
        // We need to ensure the bookings table has this column or we use a workaround
        // Assuming we can add it or use updated_at?
        // Let's add an update for approval_date if the column exists, or just use NOW().
        $updateApprovalDateSql = "UPDATE bookings SET approval_date = NOW() WHERE booking_id = :booking_id";
        try {
            $updateApprovalDateStmt = $pdo->prepare($updateApprovalDateSql);
            $updateApprovalDateStmt->execute([':booking_id' => $bookingId]);
        } catch (PDOException $e) {
            // Ignore if column doesn't exist yet (will rely on updated_at or create column later)
             if (function_exists('error_log')) {
                error_log("approve_booking.php - Warning: Could not set approval_date: " . $e->getMessage());
            }
        }
        
        // AUTO-REJECT LOGIC: Reject other pending applications for room (Private Room ONLY)
        // For Bed Spacer, we allow multiple approvals until capacity is reached?
        // User said: "Available (1 reserved)" -> suggests multiple approvals allowed for Bed Spacer.
        // So Only auto-reject if Private Room.
        
        if ($booking['room_category'] === 'Private Room') {
            if (function_exists('error_log')) {
                error_log("approve_booking.php - Auto-rejecting other pending applications for Private Room " . $booking['room_id']);
            }

            try {
                $rejectOthersSql = "
                    UPDATE bookings 
                    SET booking_status = 'Rejected', 
                        rejection_reason = 'Room reserved for another applicant' 
                    WHERE room_id = :room_id 
                    AND booking_status = 'Pending' 
                    AND booking_id != :current_booking_id
                ";
                $rejectOthersStmt = $pdo->prepare($rejectOthersSql);
                $rejectOthersStmt->execute([
                    ':room_id' => $booking['room_id'],
                    ':current_booking_id' => $bookingId
                ]);
                
                $rejectedCount = $rejectOthersStmt->rowCount();
                if (function_exists('error_log')) {
                    error_log("approve_booking.php - Rejected $rejectedCount other applications for room " . $booking['room_id']);
                }
            } catch (PDOException $e) {
                if (function_exists('error_log')) {
                    error_log("approve_booking.php - Error auto-rejecting applications: " . $e->getMessage());
                }
            }
        }
        
        // =========================================================================================
        // GENERATE PAYMENT BREAKDOWNS (Required for boarder to pay)
        // =========================================================================================
        try {
            if (isset($booking['price']) && $booking['price'] > 0) {
                $price = floatval($booking['price']);
                $startDate = new DateTime($booking['start_date']);
                $endDate = new DateTime($booking['end_date']);
                $periodStart = clone $startDate;
                $periodNum = 1;

                // Prepare insert statement
                $insertBreakdownSql = "
                    INSERT INTO payment_breakdowns (
                        booking_id, period_type, period_number, period_label, 
                        period_start_date, period_end_date, amount, 
                        is_selected, is_paid, due_date, payment_status, created_at
                    ) VALUES (
                        :booking_id, :period_type, :period_number, :period_label,
                        :period_start, :period_end, :amount,
                        0, 0, :due_date, 'Pending', NOW()
                    )
                ";
                $insertStmt = $pdo->prepare($insertBreakdownSql);

                while ($periodStart < $endDate) {
                    $periodEnd = clone $periodStart;
                    $periodEnd->modify('+1 month');
                    
                    // Cap at booking end date
                    if ($periodEnd > $endDate) {
                        $periodEnd = clone $endDate;
                    }

                    // Calculate days and amount
                    $interval = $periodStart->diff($periodEnd);
                    $daysInPeriod = $interval->days;
                    
                    // Logic: If >= 28 days, treat as full month (full price)
                    // Else, prorate: (Price / 30) * Days
                    $amount = $price;
                    $periodType = 'Month'; // Default to Month

                    if ($daysInPeriod < 28) {
                        $dailyRate = $price / 30.0;
                        $amount = $dailyRate * $daysInPeriod;
                        $periodType = 'Days';
                    }
                    
                    // Period Label
                    $label = ($periodNum == 1) ? "1st Month" : 
                             (($periodNum == 2) ? "2nd Month" : 
                             (($periodNum == 3) ? "3rd Month" : "{$periodNum}th Month"));
                    
                    if ($daysInPeriod < 28) {
                        $label = "$daysInPeriod days";
                    }

                    // Execute Insert
                    $insertStmt->execute([
                        ':booking_id' => $bookingId,
                        ':period_type' => $periodType,
                        ':period_number' => ($periodType === 'Days') ? 0 : $periodNum,
                        ':period_label' => $label,
                        ':period_start' => $periodStart->format('Y-m-d'),
                        ':period_end' => $periodEnd->format('Y-m-d'),
                        ':amount' => number_format($amount, 2, '.', ''),
                        ':due_date' => $periodStart->format('Y-m-d')
                    ]);
                    
                    // Move to next period
                    $periodStart = $periodEnd;
                    $periodNum++;
                    
                    if ($periodNum > 60) break; // Safety limit (5 years)
                }
                
                if (function_exists('error_log')) {
                    error_log("approve_booking.php - Generated " . ($periodNum - 1) . " payment breakdowns for Booking $bookingId");
                }
            } else {
                if (function_exists('error_log')) {
                    error_log("approve_booking.php - WARNING: Price is 0 or missing, cannot generate breakdowns.");
                }
            }
        } catch (Exception $e) {
            // Log but allow approval to proceed (though boarder might not be able to pay properly)
            if (function_exists('error_log')) {
                error_log("approve_booking.php - ERROR generating breakdowns: " . $e->getMessage());
            }
        }
    } else if ($currentStatus == 'Approved') {
        // Stage 4: Final Confirmation (Payment)
        $newStatus = 'Confirmed';
        $newRoomStatus = 'Occupied'; // For Private Rooms
        $message = 'Booking and payment confirmed. Room is now fully booked.';
        $notificationAction = 'Confirmed';
        $isFinalConfirmation = true;
    }
    
    // Update booking status
    $updateSql = "UPDATE bookings SET booking_status = :new_status WHERE booking_id = :booking_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([':new_status' => $newStatus, ':booking_id' => $bookingId]);
    
    // Update room status
    // For Bed Spacer, we need to check capacity
    // For Private Room, we update to 'Reserved' or 'Occupied'
    
    // Update room status logic for CONFIRMED bookings 
    // (For Approved bookings, status is set to 'Reserved' above for Private Rooms)
    if ($newStatus === 'Confirmed') {
        if ($booking['room_category'] === 'Private Room') {
            $newRoomStatus = 'Occupied';
        } else if ($booking['room_category'] === 'Bed Spacer') {
             // Count confirmed active bookings for this room (including the one we just confirmed)
             $cStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Confirmed'");
             $cStmt->execute([$booking['room_id']]);
             $confirmedCount = $cStmt->fetchColumn(); 
             // Note: The current booking is already updated to 'Confirmed' above, so it is included in the count
             
             $capacity = intval($booking['capacity']);
             
             if ($confirmedCount >= $capacity) {
                 $newRoomStatus = 'Occupied';
             } else {
                 // Any confirmed booking means at least partially occupied
                 // Use the specific status string requested
                 $newRoomStatus = 'Available(Partially Occupied)';
             }
        }
    }

    if (!empty($newRoomStatus)) {
        $updateRoomStatusSql = "UPDATE room_units SET status = :new_room_status WHERE room_id = :room_id";
        $updateRoomStatusStmt = $pdo->prepare($updateRoomStatusSql);
        $updateRoomStatusStmt->execute([':new_room_status' => $newRoomStatus, ':room_id' => $booking['room_id']]);
    }
    
    // If final confirmation, mark associated payments as Paid
    // If final confirmation, we do NOT automatically mark payments as Paid here.
    // Payment status should be updated via update_payment_status.php or similar flow
    // when the specific payment transaction is approved.
    // The booking status 'Confirmed' implies the *Booking* is confirmed, likely due to an initial payment.
    if ($isFinalConfirmation) {
        // Log that we are confirming the booking
        if (function_exists('error_log')) {
            error_log("approve_booking.php - Booking $bookingId Confirmed. Processing initial payment...");
        }
        
        // FIND AND APPROVE THE PENDING PAYMENT ASSOCIATED WITH THIS BOOKING
        // Usually there is only one pending payment at this stage (the initial/advance payment)
        $findPaymentSql = "SELECT payment_id FROM payments WHERE booking_id = :booking_id AND payment_status = 'Pending' ORDER BY payment_date DESC LIMIT 1";
        $findPaymentStmt = $pdo->prepare($findPaymentSql);
        $findPaymentStmt->execute([':booking_id' => $bookingId]);
        $payment = $findPaymentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            $paymentId = $payment['payment_id'];
            
            // 1. Update linked breakdowns to Paid
            // Only update breakdowns LINKED to this payment (submit_payment.php links them)
            $updateBreakdownSql = "UPDATE payment_breakdowns SET payment_status = 'Paid', is_paid = 1, updated_at = NOW() WHERE payment_id = :payment_id";
            $updateBreakdownStmt = $pdo->prepare($updateBreakdownSql);
            $updateBreakdownStmt->execute([':payment_id' => $paymentId]);
            
            // 2. Calculate Overall Payment Status based on breakdowns (Partially vs Fully Paid)
            // Reuse the logic: Count total periods vs paid periods
            $sqlCheckStatus = "SELECT 
                                COUNT(*) as total_periods,
                                SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_periods
                               FROM payment_breakdowns
                               WHERE booking_id = :booking_id";
            $stmtCheckStatus = $pdo->prepare($sqlCheckStatus);
            $stmtCheckStatus->execute([':booking_id' => $bookingId]);
            $statusRow = $stmtCheckStatus->fetch(PDO::FETCH_ASSOC);

            $totalPeriods = intval($statusRow['total_periods']);
            $paidPeriods = intval($statusRow['paid_periods']);
            
            $finalPaymentStatus = 'Partially Paid'; // Default if payment exists but not full
            
            if ($totalPeriods > 0) {
                if ($paidPeriods >= $totalPeriods) {
                    $finalPaymentStatus = 'Fully Paid';
                } else if ($paidPeriods > 0) {
                    $finalPaymentStatus = 'Partially Paid';
                }
            }
            
            // 3. Update Payment Record Status
            $updatePaymentSql = "UPDATE payments SET payment_status = :status, updated_at = NOW() WHERE payment_id = :payment_id";
            $updatePaymentStmt = $pdo->prepare($updatePaymentSql);
            $updatePaymentStmt->execute([
                ':status' => $finalPaymentStatus,
                ':payment_id' => $paymentId
            ]);
            
            if (function_exists('error_log')) {
                error_log("approve_booking.php - Initial Payment $paymentId approved. Status: $finalPaymentStatus ($paidPeriods/$totalPeriods paid)");
            }
        } else {
             if (function_exists('error_log')) {
                error_log("approve_booking.php - No pending payment found for booking $bookingId. Skipping payment update.");
            }
        }
        
        // =========================================================================================
        // AUTO-CANCEL other Pending/Approved applications for this user
        // =========================================================================================
        $boarderUserId = intval($booking['boarder_user_id']);
        
        if (function_exists('error_log')) {
            error_log("approve_booking.php - Checking for other applications to auto-cancel for user_id: $boarderUserId");
        }
        
        // 1. Fetch other active applications (Pending/Approved)
        $fetchOtherAppsSql = "
            SELECT 
                b.booking_id, 
                b.booking_status,
                b.room_id,
                ru.room_number,
                bhr.room_category,
                bhr.capacity
            FROM bookings b
            INNER JOIN room_units ru ON b.room_id = ru.room_id
            INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            WHERE b.user_id = :user_id 
            AND b.booking_id != :current_booking_id
            AND b.booking_status IN ('Pending', 'Approved')
            FOR UPDATE
        ";
        
        $fetchOtherAppsStmt = $pdo->prepare($fetchOtherAppsSql);
        $fetchOtherAppsStmt->execute([
            ':user_id' => $boarderUserId,
            ':current_booking_id' => $bookingId
        ]);
        $otherApps = $fetchOtherAppsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cancelledCount = 0;
        
        if ($otherApps) {
            foreach ($otherApps as $app) {
                $otherBookingId = $app['booking_id'];
                $otherRoomId = $app['room_id'];
                $otherRoomCategory = $app['room_category'];
                
                // 2. Cancel the booking
                $cancelStmt = $pdo->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = :booking_id");
                $cancelStmt->execute([':booking_id' => $otherBookingId]);
                
                // 3. Restore Room Availability
                if ($otherRoomCategory === 'Private Room') {
                    // Private room becomes Available immediately
                    $pdo->prepare("UPDATE room_units SET status = 'Available' WHERE room_id = ?")->execute([$otherRoomId]);
                } else if ($otherRoomCategory === 'Bed Spacer') {
                    // Recalculate status based on remaining bookings
                    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Confirmed'");
                    $cStmt->execute([$otherRoomId]);
                    $confirmedCount = $cStmt->fetchColumn();
                    
                    $pStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status IN ('Pending', 'Approved')");
                    $pStmt->execute([$otherRoomId]);
                    $pendingCount = $pStmt->fetchColumn(); 
                    
                    $capacity = intval($app['capacity']);
                    $otherNewRoomStatus = 'Available';
                    
                    if ($confirmedCount >= $capacity) {
                        $otherNewRoomStatus = 'Occupied';
                    } else if ($confirmedCount > 0 || $pendingCount > 0) {
                        $otherNewRoomStatus = 'Partially Occupied';
                    }
                    
                    $pdo->prepare("UPDATE room_units SET status = ? WHERE room_id = ?")->execute([$otherNewRoomStatus, $otherRoomId]);
                }
                
                // 4. Cancel any associated pending payments
                $pdo->prepare("UPDATE payments SET payment_status = 'Cancelled' WHERE booking_id = ? AND payment_status = 'Pending'")->execute([$otherBookingId]);
                
                $cancelledCount++;
                
                // Send notification to boarder about auto-cancellation (deferred validation, just log for now)
                if (function_exists('error_log')) {
                    error_log("approve_booking.php - Auto-cancelled booking $otherBookingId due to confirmation of booking $bookingId");
                }
                
                // Note: We'll rely on the main notification system or can add ActivityNotifications call here if needed
                // For now, let's keep it simple to ensure transaction success
            }
        }
        
        if ($cancelledCount > 0) {
            $message .= " $cancelledCount other pending application(s) have been automatically cancelled.";
        }

        // =========================================================================================
        // ADD TO ACTIVE BOARDERS
        // =========================================================================================
        $roomId = intval($booking['room_id']);
        //$boardingHouseId = intval($booking['boarding_house_id']); // Not needed for insert if removed from schema, but let's see
        
        if (function_exists('error_log')) {
            error_log("approve_booking.php - Processing active_boarders for user_id: $boarderUserId, room_id: $roomId");
        }
        
        // Check if boarder already exists in active_boarders for this exact room
        $checkActiveSql = "
            SELECT active_id, status 
            FROM active_boarders 
            WHERE user_id = :user_id 
            AND room_id = :room_id
        ";
        $checkActiveStmt = $pdo->prepare($checkActiveSql);
        $checkActiveStmt->execute([
            ':user_id' => $boarderUserId,
            ':room_id' => $roomId
        ]);
        $existingActive = $checkActiveStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingActive) {
            // Update existing record to Active status
            $updateActiveSql = "UPDATE active_boarders SET status = 'Active' WHERE active_id = :active_id";
            $updateActiveStmt = $pdo->prepare($updateActiveSql);
            $updateActiveStmt->execute([':active_id' => $existingActive['active_id']]);
            
            if (function_exists('error_log')) {
                error_log("approve_booking.php - Updated active_boarders record {$existingActive['active_id']} to Active");
            }
        } else {
            // Insert new record
            $insertActiveSql = "INSERT INTO active_boarders (user_id, status, room_id) VALUES (:user_id, 'Active', :room_id)";
            $insertActiveStmt = $pdo->prepare($insertActiveSql);
            $insertActiveStmt->execute([
                ':user_id' => $boarderUserId,
                ':room_id' => $roomId
            ]);
            
            if (function_exists('error_log')) {
                error_log("approve_booking.php - Inserted new active_boarders record for user $boarderUserId");
            }
        }
    }
    
    if (function_exists('error_log')) {
        error_log("approve_booking.php - Booking ID $bookingId transitioned from $currentStatus to $newStatus.");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare response data
    $responseData = array(
        'success' => true,
        'message' => $message,
        'status' => $newStatus,
        'booking_id' => $bookingId,
        'should_refresh' => true,
        'navigate_back' => true
    );
    
    // Convert to JSON
    $jsonResponse = json_encode($responseData);
    
    // Clear buffer and prepare to send
    ob_clean(); // Clear any previous output
    
    // Set headers for non-blocking response
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    header('Content-Length: ' . strlen($jsonResponse));
    
    // Send response
    echo $jsonResponse;
    
    // Force output to be sent to client
    ob_end_flush();
    flush();
    
    // Close session if open
    if (session_id()) session_write_close();
    
    // If using PHP-FPM, this finishes the request for the user but keeps script running
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    // Send notifications AFTER response is sent (now safely in background)
    try {
        // Get booking details for notifications
        $getNotificationDetailsSql = "
            SELECT 
                b.user_id as boarder_user_id,
                bhr.room_name,
                bh.bh_name,
                bh.user_id as owner_user_id
            FROM bookings b
            JOIN room_units ru ON b.room_id = ru.room_id
            JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE b.booking_id = ?
        ";
        $getNotificationDetailsStmt = $pdo->prepare($getNotificationDetailsSql);
        $getNotificationDetailsStmt->execute(array($bookingId));
        $notificationDetails = $getNotificationDetailsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notificationDetails) {
            $boarderUserId = $notificationDetails['boarder_user_id'];
            $roomName = $notificationDetails['room_name'];
            $bhName = $notificationDetails['bh_name'];
            
            // Send notifications using ActivityNotifications class
            try {
                require_once 'activity_notifications.php';
                if (class_exists('ActivityNotifications')) {
                    // Send booking approval notification to boarder
                    ActivityNotifications::notifyBookingApproved($boarderUserId, array(
                        'room_name' => $roomName,
                        'bh_name' => $bhName,
                        'booking_id' => $bookingId
                    ));
                    if (function_exists('error_log')) {
                        error_log("Booking approval notification sent to boarder (user_id: $boarderUserId)");
                    }
                }
            } catch (Exception $e) {
                if (function_exists('error_log')) {
                    error_log("Warning: Failed to send notifications: " . $e->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        if (function_exists('error_log')) {
            error_log("Warning: Failed to send notifications: " . $e->getMessage());
        }
    }

    // NEW: Send Real-time Refresh Trigger to Boarder
    try {
        require_once 'fcm_config.php';
        require_once 'dbConfig.php'; 
        
        // Get ALL boarder's device tokens to ensure we hit the right one
        $getTokenSql = "SELECT device_token FROM device_tokens WHERE user_id = ? AND is_active = 1";
        $getTokenStmt = $pdo->prepare($getTokenSql);
        $getTokenStmt->execute([$booking['boarder_user_id']]); 
        $tokens = $getTokenStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($tokens && count($tokens) > 0) {
             if (function_exists('error_log')) {
                 error_log("approve_booking.php - Found " . count($tokens) . " tokens for user " . $booking['boarder_user_id']);
             }

             $dataPayload = [
                 'action' => 'refresh_bookings', // The trigger key
                 'type' => 'booking_update',
                 'booking_id' => (string)$bookingId,
                 'status' => $newStatus 
             ];
             
             foreach ($tokens as $tokenRow) {
                 if (!empty($tokenRow['device_token'])) {
                     $deviceToken = $tokenRow['device_token'];
                     // Send Data Message (High Priority)
                     $fcmResult = FCMConfig::sendDataMessage($deviceToken, $dataPayload);
                     
                     if (function_exists('error_log')) {
                         error_log("approve_booking.php - Refresh sent to token " . substr($deviceToken, 0, 10) . "... Result: " . json_encode($fcmResult));
                     }
                 }
             }
        } else {
             if (function_exists('error_log')) {
                 error_log("approve_booking.php - No device tokens found for real-time refresh (user " . $booking['boarder_user_id'] . ")");
             }
        }
        
    } catch (Exception $e) {
         if (function_exists('error_log')) {
             error_log("approve_booking.php - Failed to trigger real-time refresh: " . $e->getMessage());
         }
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (function_exists('error_log')) {
        error_log("Database error in approve_booking.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error occurred. Please try again.'
    ));
    ob_end_flush();
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (function_exists('error_log')) {
        error_log("Error in approve_booking.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error occurred. Please try again.'
    ));
    ob_end_flush();
} catch (Error $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (function_exists('error_log')) {
        error_log("Fatal error in approve_booking.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Fatal error occurred. Please try again.'
    ));
    ob_end_flush();
}

