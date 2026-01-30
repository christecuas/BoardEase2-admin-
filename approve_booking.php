<?php
// Suppress all error output to prevent HTML from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

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
        // If booking is already Confirmed, return success (idempotent operation)
        if ($booking['booking_status'] == 'Confirmed') {
            echo json_encode(array(
                'success' => true,
                'message' => 'Booking is already confirmed',
                'status' => 'Confirmed',
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
    
    // Update booking status to Confirmed
    $updateSql = "UPDATE bookings SET booking_status = 'Confirmed' WHERE booking_id = :booking_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([':booking_id' => $bookingId]);
    
    // Check payment breakdown to see if payments have been made
    // Also check if payment proof (screenshot) exists - if proof exists, payment was made
    $checkPaymentProgressSql = "
        SELECT 
            COUNT(*) as total_periods,
            SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_periods
        FROM payment_breakdowns
        WHERE booking_id = :booking_id
    ";
    $checkProgressStmt = $pdo->prepare($checkPaymentProgressSql);
    $checkProgressStmt->execute([':booking_id' => $bookingId]);
    $paymentProgress = $checkProgressStmt->fetch(PDO::FETCH_ASSOC);
    
    $totalPeriods = intval($paymentProgress['total_periods']);
    $paidPeriods = intval($paymentProgress['paid_periods']);
    
    // Check if payment proof exists (payment_proof or receipt_url)
    // If proof exists, it means payment was made even if is_paid = 0
    $checkPaymentProofSql = "
        SELECT 
            payment_id,
            payment_status,
            payment_proof,
            receipt_url,
            payment_amount
        FROM payments
        WHERE booking_id = :booking_id
        ORDER BY payment_id DESC, updated_at DESC
        LIMIT 1
    ";
    $checkPaymentProofStmt = $pdo->prepare($checkPaymentProofSql);
    $checkPaymentProofStmt->execute([':booking_id' => $bookingId]);
    $paymentData = $checkPaymentProofStmt->fetch(PDO::FETCH_ASSOC);
    
    $hasPaymentProof = false;
    if ($paymentData) {
        $paymentProof = $paymentData['payment_proof'];
        $receiptUrl = $paymentData['receipt_url'];
        // Check if payment proof exists (not null, not empty, not 'null')
        $hasPaymentProof = (!empty($paymentProof) && $paymentProof !== 'null') || 
                          (!empty($receiptUrl) && $receiptUrl !== 'null');
    }
    
    // Determine payment status based on payment breakdown progress AND payment proof
    $paymentStatus = 'Pending'; // Default
    if ($totalPeriods > 0) {
        // If payment proof exists, consider those periods as paid
        // Count periods that have payment proof OR is_paid = 1
        if ($hasPaymentProof) {
            // Payment proof exists - check how many periods have proof AND were selected
            // Only count periods that were actually selected for payment (is_selected = 1)
            $checkPeriodsWithProofSql = "
                SELECT COUNT(*) as periods_with_proof
                FROM payment_breakdowns
                WHERE booking_id = :booking_id
                AND payment_id IS NOT NULL
                AND is_selected = 1
            ";
            $checkPeriodsStmt = $pdo->prepare($checkPeriodsWithProofSql);
            $checkPeriodsStmt->execute([':booking_id' => $bookingId]);
            $periodsWithProof = $checkPeriodsStmt->fetch(PDO::FETCH_ASSOC);
            $periodsWithProofCount = intval($periodsWithProof['periods_with_proof']);
            
            // If payment proof exists, mark those periods as paid
            if ($periodsWithProofCount > 0) {
                // Update is_paid = 1 ONLY for periods that have payment_id AND were selected for payment (is_selected = 1)
                // CRITICAL: Only mark periods that were actually selected - don't mark all periods with payment_id
                $markPeriodsPaidSql = "
                    UPDATE payment_breakdowns
                    SET is_paid = 1,
                        payment_status = 'Paid',
                        updated_at = NOW()
                    WHERE booking_id = :booking_id
                    AND payment_id IS NOT NULL
                    AND is_selected = 1
                    AND is_paid = 0
                ";
                $markPaidStmt = $pdo->prepare($markPeriodsPaidSql);
                $markPaidStmt->execute([':booking_id' => $bookingId]);
                $markedPaid = $markPaidStmt->rowCount();
                
                // Recalculate paid periods after marking
                $checkProgressStmt->execute([':booking_id' => $bookingId]);
                $paymentProgress = $checkProgressStmt->fetch(PDO::FETCH_ASSOC);
                $paidPeriods = intval($paymentProgress['paid_periods']);
                
                if (function_exists('error_log')) {
                    error_log("approve_booking.php - Marked $markedPaid period(s) as paid due to payment proof");
                }
            }
        }
        
        // Now determine status based on updated paid periods
        if ($paidPeriods >= $totalPeriods) {
            // All periods paid - Fully Paid
            $paymentStatus = 'Fully Paid';
        } else if ($paidPeriods > 0 || $hasPaymentProof) {
            // Some periods paid OR payment proof exists - Partially Paid
            $paymentStatus = 'Partially Paid';
        } else {
            // No periods paid and no proof - Pending
            $paymentStatus = 'Pending';
        }
    } else {
        // No breakdown exists - check if payment proof exists
        if ($hasPaymentProof) {
            // Payment proof exists but no breakdown - mark as Partially Paid
            $paymentStatus = 'Partially Paid';
        } else if ($paymentData) {
            // Payment exists but no proof and no breakdown
            $paymentStatus = $paymentData['payment_status'] ?: 'Pending';
        } else {
            $paymentStatus = 'Pending';
        }
    }
    
    if (function_exists('error_log')) {
        error_log("approve_booking.php - Payment progress: $paidPeriods/$totalPeriods periods paid, setting status to: $paymentStatus");
    }
    
    // Update payment status based on payment breakdown progress
    // This automatically marks payments as paid if breakdown shows is_paid = 1
    $updatePaymentSql = "UPDATE payments SET payment_status = :payment_status, updated_at = NOW() WHERE booking_id = :booking_id";
    $updatePaymentStmt = $pdo->prepare($updatePaymentSql);
    $updatePaymentStmt->execute([
        ':payment_status' => $paymentStatus,
        ':booking_id' => $bookingId
    ]);
    
    // Also update payment_breakdowns payment_status to match
    // Only update breakdowns that are already marked as paid (is_paid = 1)
    if ($paidPeriods > 0) {
        $breakdownStatusMap = [
            'Fully Paid' => 'Paid',
            'Partially Paid' => 'Paid',
            'Pending' => 'Pending'
        ];
        $breakdownStatus = isset($breakdownStatusMap[$paymentStatus]) ? $breakdownStatusMap[$paymentStatus] : 'Pending';
        
        $updateBreakdownStatusSql = "
            UPDATE payment_breakdowns 
            SET payment_status = :breakdown_status,
                updated_at = NOW()
            WHERE booking_id = :booking_id
            AND is_paid = 1
        ";
        $updateBreakdownStatusStmt = $pdo->prepare($updateBreakdownStatusSql);
        $updateBreakdownStatusStmt->execute([
            ':breakdown_status' => $breakdownStatus,
            ':booking_id' => $bookingId
        ]);
        
        if (function_exists('error_log')) {
            $affectedBreakdowns = $updateBreakdownStatusStmt->rowCount();
            error_log("approve_booking.php - Updated $affectedBreakdowns payment breakdown(s) to status: $breakdownStatus");
        }
    }
    
    // Update room status when booking is confirmed
    // Room was 'Partially Occupied' (Reserved) when booking was Pending
    // Now update to 'Occupied' (for Private Room) or based on capacity (for Bed Spacer)
    $roomId = $booking['room_id'];
    $roomCategory = $booking['room_category'] ?? '';
    $capacity = isset($booking['capacity']) ? intval($booking['capacity']) : 1;
    $startDate = $booking['start_date'] ?? '';
    $endDate = $booking['end_date'] ?? '';
    
    if ($roomCategory === 'Private Room') {
        // Private Room: Change from 'Partially Occupied' (Reserved) to 'Occupied' (Fully booked)
        $updateRoomStatusSql = "UPDATE room_units SET status = 'Occupied' WHERE room_id = :room_id";
        $updateRoomStatusStmt = $pdo->prepare($updateRoomStatusSql);
        $updateRoomStatusStmt->execute([':room_id' => $roomId]);
        
        if (function_exists('error_log')) {
            error_log("approve_booking.php - Private Room room_id=$roomId updated from 'Partially Occupied' (Reserved) to 'Occupied' (Fully booked)");
        }
        
    } else if ($roomCategory === 'Bed Spacer' && $capacity > 0) {
        // Bed Spacer: Count CONFIRMED bookings (including this one we just confirmed) for the same dates
        $countConfirmedBookingsSql = "
            SELECT COUNT(b2.booking_id) as confirmed_count
            FROM bookings b2
            WHERE b2.room_id = :room_id
            AND b2.booking_status = 'Confirmed'
            AND (
                (b2.start_date <= :start_date AND b2.end_date >= :start_date)
                OR (b2.start_date <= :end_date AND b2.end_date >= :end_date)
                OR (b2.start_date >= :start_date AND b2.end_date <= :end_date)
            )
        ";
        $countStmt = $pdo->prepare($countConfirmedBookingsSql);
        $countStmt->execute([
            ':room_id' => $roomId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $confirmedCount = intval($countResult['confirmed_count']);
        
        if (function_exists('error_log')) {
            error_log("approve_booking.php - Bed Spacer room_id=$roomId: confirmed_count=$confirmedCount, capacity=$capacity");
        }
        
        // Only update to 'Occupied' if capacity is reached
        if ($confirmedCount >= $capacity) {
            $updateRoomStatusSql = "UPDATE room_units SET status = 'Occupied' WHERE room_id = :room_id";
            $updateRoomStatusStmt = $pdo->prepare($updateRoomStatusSql);
            $updateRoomStatusStmt->execute([':room_id' => $roomId]);
            
            if (function_exists('error_log')) {
                error_log("approve_booking.php - Bed Spacer room_id=$roomId updated from 'Partially Occupied' (Reserved) to 'Occupied' (capacity reached: $confirmedCount/$capacity)");
            }
        } else {
            // Still has capacity, keep as 'Partially Occupied' (even if no pending bookings)
            // Status should remain 'Partially Occupied' until capacity is full (then becomes 'Occupied')
            $updateRoomStatusSql = "UPDATE room_units SET status = 'Partially Occupied' WHERE room_id = :room_id";
            $updateRoomStatusStmt = $pdo->prepare($updateRoomStatusSql);
            $updateRoomStatusStmt->execute([':room_id' => $roomId]);
            
            if (function_exists('error_log')) {
                error_log("approve_booking.php - Bed Spacer room_id=$roomId remains 'Partially Occupied' (capacity not reached: $confirmedCount/$capacity)");
            }
        }
    }
    
    // Insert or update active_boarders table
    // This automatically adds the boarder to the active boarders list when booking is approved
    $boarderUserId = $booking['boarder_user_id'];
    $boardingHouseId = $booking['boarding_house_id'];
    
    if (function_exists('error_log')) {
        error_log("approve_booking.php - Processing active_boarders: user_id=$boarderUserId, room_id=$roomId, boarding_house_id=$boardingHouseId");
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
        $updateActiveSql = "
            UPDATE active_boarders 
            SET status = 'Active' 
            WHERE active_id = :active_id
        ";
        $updateActiveStmt = $pdo->prepare($updateActiveSql);
        $updateActiveStmt->execute([':active_id' => $existingActive['active_id']]);
        
        if (function_exists('error_log')) {
            error_log("approve_booking.php - Updated existing active_boarders record (active_id: {$existingActive['active_id']}) to Active status");
        }
    } else {
        // Insert new record into active_boarders (boarding_house_id removed - derived from room_id)
        // This automatically happens when booking is approved
        $insertActiveSql = "
            INSERT INTO active_boarders (user_id, status, room_id) 
            VALUES (:user_id, 'Active', :room_id)
        ";
        $insertActiveStmt = $pdo->prepare($insertActiveSql);
        $insertActiveStmt->execute([
            ':user_id' => $boarderUserId,
            ':room_id' => $roomId
        ]);
        $activeId = $pdo->lastInsertId();
        
        if (function_exists('error_log')) {
            error_log("approve_booking.php - Successfully inserted new active_boarders record (active_id: $activeId) for user_id: $boarderUserId, room_id: $roomId");
        }
        
        if ($activeId == 0) {
            // Insert failed - log error but don't fail the transaction
            if (function_exists('error_log')) {
                error_log("approve_booking.php - WARNING: Failed to insert into active_boarders - lastInsertId is 0");
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare response data first - ensure no 'error' field when success is true
    $responseData = array(
        'success' => true,
        'message' => 'Booking confirmed successfully',
        'status' => 'Confirmed',
        'booking_id' => $bookingId,
        'payment_status' => $paymentStatus, // Include payment status in response
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
    
    // Send notifications AFTER response is sent (non-blocking)
    try {
        // Get booking and payment details for notifications
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
            $ownerUserId = $notificationDetails['owner_user_id'];
            $roomName = $notificationDetails['room_name'];
            $bhName = $notificationDetails['bh_name'];
            
            // Get total paid amount from payment breakdowns (sum of all paid periods)
            $getTotalPaidSql = "
                SELECT COALESCE(SUM(amount), 0) as total_paid
                FROM payment_breakdowns
                WHERE booking_id = ? AND is_paid = 1
            ";
            $getTotalPaidStmt = $pdo->prepare($getTotalPaidSql);
            $getTotalPaidStmt->execute(array($bookingId));
            $totalPaidResult = $getTotalPaidStmt->fetch(PDO::FETCH_ASSOC);
            $paymentAmount = floatval($totalPaidResult['total_paid'] ?? 0);
            
            // If no breakdown data, try to get from payments table
            if ($paymentAmount == 0) {
                $getPaymentAmountSql = "
                    SELECT COALESCE(SUM(payment_amount), 0) as total_payment
                    FROM payments
                    WHERE booking_id = ?
                ";
                $getPaymentAmountStmt = $pdo->prepare($getPaymentAmountSql);
                $getPaymentAmountStmt->execute(array($bookingId));
                $paymentAmountResult = $getPaymentAmountStmt->fetch(PDO::FETCH_ASSOC);
                $paymentAmount = floatval($paymentAmountResult['total_payment'] ?? 0);
            }
            
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
                    
                    // Send payment notifications if payment status is not "Pending"
                    if ($paymentStatus !== 'Pending' && $paymentAmount > 0) {
                        // 1. Send "Payment Received" notification to OWNER
                        if ($ownerUserId > 0) {
                            ActivityNotifications::notifyPaymentReceived($ownerUserId, array(
                                'amount' => $paymentAmount,
                                'description' => " from boarder for $roomName at $bhName. Status: $paymentStatus"
                            ));
                            if (function_exists('error_log')) {
                                error_log("Payment Received notification sent to owner (user_id: $ownerUserId, amount: $paymentAmount)");
                            }
                        }
                        
                        // 2. Send "Payment Status Updated" notification to BOARDER
                        if ($boarderUserId > 0) {
                            ActivityNotifications::notifyPaymentStatusUpdated($boarderUserId, array(
                                'amount' => $paymentAmount,
                                'status' => $paymentStatus
                            ));
                            if (function_exists('error_log')) {
                                error_log("Payment Status Updated notification sent to boarder (user_id: $boarderUserId, status: $paymentStatus)");
                            }
                        }
                    } else {
                        if (function_exists('error_log')) {
                            error_log("Payment status is Pending or amount is 0 - skipping payment notifications");
                        }
                    }
                } else {
                    if (function_exists('error_log')) {
                        error_log("ActivityNotifications class not found - skipping notifications");
                    }
                }
            } catch (Exception $e) {
                // Don't fail the approval if notification fails
                if (function_exists('error_log')) {
                    error_log("Warning: Failed to send notifications: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            }
        } else {
            if (function_exists('error_log')) {
                error_log("Warning: Could not send notifications - booking details not found");
            }
        }
    } catch (Exception $e) {
        // Don't fail the approval if notification fails
        if (function_exists('error_log')) {
            error_log("Warning: Failed to send notifications: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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
                 'status' => 'Confirmed' 
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

