<?php
// Disable output buffering and ensure clean output
ob_start();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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

// Clear any previous output
ob_clean();

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get POST data
    $json = file_get_contents('php://input');
    error_log("decline_booking.php - Received JSON: " . $json);
    
    $data = json_decode($json, true);
    
    if ($data === null) {
        error_log("decline_booking.php - JSON decode failed: " . json_last_error_msg());
        ob_clean();
        echo json_encode(array(
            'success' => false,
            'error' => 'Invalid JSON data: ' . json_last_error_msg()
        ));
        ob_end_flush();
        exit;
    }
    
    error_log("decline_booking.php - Decoded data: " . print_r($data, true));
    
    $bookingId = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
    $ownerId = isset($data['owner_id']) ? intval($data['owner_id']) : 0;
    $reason = isset($data['reason']) ? trim($data['reason']) : 'Declined by owner';
    
    error_log("decline_booking.php - booking_id: $bookingId, owner_id: $ownerId, reason: $reason");
    
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
    // Also get room category and capacity for proper status update
    // boarding_houses.user_id refers to users.user_id directly
    // Use FOR UPDATE to lock the row and prevent double-processing
    $verifySql = "
        SELECT 
            b.booking_id, 
            b.booking_status, 
            b.room_id,
            b.start_date,
            b.end_date,
            bh.user_id as owner_user_id,
            bhr.room_category,
            bhr.capacity
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
    if ($booking['booking_status'] != 'Pending') {
        $pdo->rollBack();
        ob_clean();
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        // If booking is already Cancelled, return success (idempotent operation)
        if ($booking['booking_status'] == 'Cancelled') {
            echo json_encode(array(
                'success' => true,
                'message' => 'Booking is already cancelled',
                'status' => 'Cancelled',
                'booking_id' => $bookingId
            ), JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(array(
                'success' => false,
                'error' => 'Booking is already ' . $booking['booking_status'],
                'booking_id' => $bookingId
            ), JSON_UNESCAPED_SLASHES);
        }
        ob_end_flush();
        exit;
    }
    
    // Update booking status to Cancelled
    $updateSql = "UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = :booking_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([':booking_id' => $bookingId]);
    
    // Update room status based on room category
    $roomId = $booking['room_id'];
    $roomCategory = $booking['room_category'] ?? '';
    $capacity = isset($booking['capacity']) ? intval($booking['capacity']) : 1;
    
    if ($roomCategory === 'Private Room') {
        // Private Room: Set back to 'Available' when booking is cancelled
        $updateRoomSql = "UPDATE room_units SET status = 'Available' WHERE room_id = :room_id";
        $updateRoomStmt = $pdo->prepare($updateRoomSql);
        $updateRoomStmt->execute([':room_id' => $roomId]);
        
        if (function_exists('error_log')) {
            error_log("decline_booking.php - Private Room room_id=$roomId updated from 'Partially Occupied' (Reserved) to 'Available' (booking cancelled)");
        }
        
    } else if ($roomCategory === 'Bed Spacer' && $capacity > 0) {
        // Bed Spacer: Check if there are other active bookings (Pending or Confirmed)
        $checkActiveBookingsSql = "
            SELECT COUNT(b2.booking_id) as active_count
            FROM bookings b2
            WHERE b2.room_id = :room_id
            AND b2.booking_status IN ('Pending', 'Confirmed')
            AND b2.booking_id != :booking_id
        ";
        $checkActiveStmt = $pdo->prepare($checkActiveBookingsSql);
        $checkActiveStmt->execute([
            ':room_id' => $roomId,
            ':booking_id' => $bookingId
        ]);
        $activeResult = $checkActiveStmt->fetch(PDO::FETCH_ASSOC);
        $activeCount = intval($activeResult['active_count']);
        
        if (function_exists('error_log')) {
            error_log("decline_booking.php - Bed Spacer room_id=$roomId: active_count=$activeCount, capacity=$capacity");
        }
        
        if ($activeCount == 0) {
            // No other active bookings, set to 'Available'
            $updateRoomSql = "UPDATE room_units SET status = 'Available' WHERE room_id = :room_id";
            $updateRoomStmt = $pdo->prepare($updateRoomSql);
            $updateRoomStmt->execute([':room_id' => $roomId]);
            
            if (function_exists('error_log')) {
                error_log("decline_booking.php - Bed Spacer room_id=$roomId updated to 'Available' (no other active bookings)");
            }
        } else {
            // There are other active bookings, check if room should be 'Occupied' or 'Available'
            // Count CONFIRMED bookings to determine if capacity is reached
            $countConfirmedSql = "
                SELECT COUNT(b2.booking_id) as confirmed_count
                FROM bookings b2
                WHERE b2.room_id = :room_id
                AND b2.booking_status = 'Confirmed'
            ";
            $countConfirmedStmt = $pdo->prepare($countConfirmedSql);
            $countConfirmedStmt->execute([':room_id' => $roomId]);
            $confirmedResult = $countConfirmedStmt->fetch(PDO::FETCH_ASSOC);
            $confirmedCount = intval($confirmedResult['confirmed_count']);
            
            if ($confirmedCount >= $capacity) {
                // At full capacity with confirmed bookings, set to 'Occupied'
                $updateRoomSql = "UPDATE room_units SET status = 'Occupied' WHERE room_id = :room_id";
                $updateRoomStmt = $pdo->prepare($updateRoomSql);
                $updateRoomStmt->execute([':room_id' => $roomId]);
                
                if (function_exists('error_log')) {
                    error_log("decline_booking.php - Bed Spacer room_id=$roomId updated to 'Occupied' (capacity reached: $confirmedCount/$capacity)");
                }
            } else {
                // Still has capacity, check if there are pending bookings
                $countPendingSql = "
                    SELECT COUNT(*) as pending_count
                    FROM bookings
                    WHERE room_id = :room_id
                    AND booking_status = 'Pending'
                ";
                $countPendingStmt = $pdo->prepare($countPendingSql);
                $countPendingStmt->execute([':room_id' => $roomId]);
                $pendingResult = $countPendingStmt->fetch(PDO::FETCH_ASSOC);
                $pendingCount = intval($pendingResult['pending_count']);
                
                if ($pendingCount > 0) {
                    // Has pending bookings, set to 'Partially Occupied'
                    $updateRoomSql = "UPDATE room_units SET status = 'Partially Occupied' WHERE room_id = :room_id";
                    $updateRoomStmt = $pdo->prepare($updateRoomSql);
                    $updateRoomStmt->execute([':room_id' => $roomId]);
                    
                    if (function_exists('error_log')) {
                        error_log("decline_booking.php - Bed Spacer room_id=$roomId updated to 'Partially Occupied' (capacity not reached: $confirmedCount/$capacity, pending: $pendingCount)");
                    }
                } else {
                    // No pending bookings, set to 'Available'
                    $updateRoomSql = "UPDATE room_units SET status = 'Available' WHERE room_id = :room_id";
                    $updateRoomStmt = $pdo->prepare($updateRoomSql);
                    $updateRoomStmt->execute([':room_id' => $roomId]);
                    
                    if (function_exists('error_log')) {
                        error_log("decline_booking.php - Bed Spacer room_id=$roomId updated to 'Available' (capacity not reached: $confirmedCount/$capacity, no pending bookings)");
                    }
                }
            }
        }
    } else {
        // Unknown category or invalid capacity, set to 'Available'
        $updateRoomSql = "UPDATE room_units SET status = 'Available' WHERE room_id = :room_id";
        $updateRoomStmt = $pdo->prepare($updateRoomSql);
        $updateRoomStmt->execute([':room_id' => $roomId]);
    }
    
    // Update payment status to Cancelled if payment exists
    $updatePaymentSql = "UPDATE payments SET payment_status = 'Cancelled' WHERE booking_id = :booking_id";
    $updatePaymentStmt = $pdo->prepare($updatePaymentSql);
    $updatePaymentStmt->execute([':booking_id' => $bookingId]);
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare response data first - ensure no 'error' field when success is true
    $responseData = array(
        'success' => true,
        'message' => 'Booking cancelled successfully',
        'status' => 'Cancelled',
        'booking_id' => $bookingId,
        'should_refresh' => true, // Flag to tell frontend to refresh/navigate back
        'navigate_back' => true   // Flag to tell frontend to navigate back to previous screen
    );
    
    // Send response IMMEDIATELY to prevent timeout
    ob_clean();
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($responseData, JSON_UNESCAPED_SLASHES);
    
    // Flush output buffer to send response immediately
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request(); // For FastCGI - sends response and closes connection
    } else {
        ob_end_flush(); // For regular PHP - sends response but keeps connection open
    }
    
    // Allow script to continue even if client disconnects
    ignore_user_abort(true);
    set_time_limit(30); // Give notifications 30 seconds to complete
    
    // Send notification to boarder AFTER response is sent (non-blocking)
    try {
        require_once 'activity_notifications.php';
        
        // Get boarder user_id and booking details for notification
        $getBookingDetailsSql = "
            SELECT 
                b.user_id as boarder_user_id,
                bhr.room_name,
                bh.bh_name
            FROM bookings b
            JOIN room_units ru ON b.room_id = ru.room_id
            JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE b.booking_id = ?
        ";
        $getBookingDetailsStmt = $pdo->prepare($getBookingDetailsSql);
        $getBookingDetailsStmt->execute([$bookingId]);
        $bookingDetails = $getBookingDetailsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bookingDetails && $bookingDetails['boarder_user_id']) {
            ActivityNotifications::notifyBookingDeclined($bookingDetails['boarder_user_id'], [
                'room_name' => $bookingDetails['room_name'],
                'bh_name' => $bookingDetails['bh_name'],
                'booking_id' => $bookingId,
                'reason' => $reason
            ]);
            if (function_exists('error_log')) {
                error_log("Notification sent to boarder (user_id: " . $bookingDetails['boarder_user_id'] . ") about booking decline");
            }
        } else {
            if (function_exists('error_log')) {
                error_log("Warning: Could not send booking decline notification - booking details not found or boarder_user_id is missing");
            }
        }
    } catch (Exception $e) {
        // Don't fail the decline if notification fails
        if (function_exists('error_log')) {
            error_log("Warning: Failed to send booking decline notification: " . $e->getMessage());
        }
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in decline_booking.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
    ob_end_flush();
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in decline_booking.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
    ob_end_flush();
}
?>

