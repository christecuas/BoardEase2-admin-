<?php
// Enable error reporting and logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Start output buffering early to catch any output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get JSON input
    $json = file_get_contents('php://input');
    if ($json === false) {
        $json = '';
    }
    $data = json_decode($json, true);
    
    // Log to error log (not output)
    if (function_exists('error_log')) {
    error_log("=== UPDATE PAYMENT STATUS REQUEST ===");
    error_log("Received JSON: " . $json);
        if ($data !== null) {
    error_log("Decoded data: " . print_r($data, true));
        }
    }
    
    // Validate input
    if (!isset($data['payment_id']) || !isset($data['status'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: payment_id and status are required'
        ]);
        ob_end_flush();
        exit;
    }
    
    $paymentId = intval($data['payment_id']);
    $newStatus = trim($data['status']);
    $notes = isset($data['notes']) ? trim($data['notes']) : '';
    
    // Validate status
    // Updated enum: 'Pending','Partially Paid','Fully Paid','Failed','Refunded'
    $validStatuses = ['Pending', 'Paid', 'Completed', 'Partially Paid', 'Fully Paid', 'Overdue', 'Failed', 'Cancelled', 'Refunded'];
    $statusMap = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'completed' => 'Completed',
        'partially paid' => 'Partially Paid',
        'partially_paid' => 'Partially Paid',
        'partially' => 'Partially Paid',
        // Legacy support for old "Completed/Partially" status
        'completed/partially' => 'Partially Paid',
        'completed_partially' => 'Partially Paid',
        'fully paid' => 'Fully Paid',
        'fully_paid' => 'Fully Paid',
        'fullypaid' => 'Fully Paid',
        'overdue' => 'Overdue',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded'
    ];
    
    // Normalize status (convert lowercase to proper case)
    if (isset($statusMap[strtolower($newStatus)])) {
        $newStatus = $statusMap[strtolower($newStatus)];
    }
    
    if (!in_array($newStatus, $validStatuses)) {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses)
        ]);
        ob_end_flush();
        exit;
    }
    
    // Log to error log (not output)
    if (function_exists('error_log')) {
    error_log("Updating payment ID: $paymentId, New status: $newStatus");
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Check if payment exists and lock the row to prevent double-processing
        $checkStmt = $pdo->prepare("SELECT payment_id, payment_status FROM payments WHERE payment_id = :payment_id FOR UPDATE");
        $checkStmt->execute([':payment_id' => $paymentId]);
        $payment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception("Payment not found with ID: $paymentId");
        }
        
        // Prevent double-processing: Check if status is already the target status
        if ($payment['payment_status'] === $newStatus) {
            $pdo->rollBack();
            ob_clean();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Payment status is already ' . $newStatus,
                'data' => [
                    'payment_id' => $paymentId,
                    'old_status' => $payment['payment_status'],
                    'new_status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);
            ob_end_flush();
            exit;
        }
        
        // Log to error log (not output)
        if (function_exists('error_log')) {
        error_log("Current payment status: " . $payment['payment_status']);
        }
        
        // NOTE: We DON'T update payments.payment_status here yet
        // We'll update breakdowns first, then auto-calculate the final status from breakdowns
        // This ensures breakdowns are the single source of truth
        
        // Update notes if provided (notes can be updated independently)
        if (!empty($notes)) {
            $notesUpdateStmt = $pdo->prepare("UPDATE payments SET notes = :notes, updated_at = NOW() WHERE payment_id = :payment_id");
            $notesUpdateStmt->execute([
                ':notes' => $notes,
                ':payment_id' => $paymentId
            ]);
        }
        
        // IMPORTANT: Update ONLY the payment_breakdowns linked to this specific payment_id
        // This handles partial payments correctly (e.g., 1/2 periods paid)
        // Only breakdowns with payment_id = X will be updated, others remain unchanged
        
        // First, check how many breakdowns are linked to this payment
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_breakdowns WHERE payment_id = :payment_id");
        $countStmt->execute([':payment_id' => $paymentId]);
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $linkedBreakdowns = intval($countResult['count']);
        
        if (function_exists('error_log')) {
            error_log("Payment ID $paymentId is linked to $linkedBreakdowns breakdown period(s)");
        }
        
        // Map payments.payment_status to payment_breakdowns.payment_status
        $breakdownStatusMap = [
            'Paid' => 'Paid',
            'Completed' => 'Paid',
            'Partially Paid' => 'Paid',  // Partial payment - mark selected periods as paid
            'Fully Paid' => 'Paid',           // Fully paid - mark selected periods as paid
            'Pending' => 'Pending',
            'Overdue' => 'Overdue',
            'Failed' => 'Cancelled',
            'Cancelled' => 'Cancelled',
            'Refunded' => 'Cancelled'
        ];
        
        $breakdownStatus = isset($breakdownStatusMap[$newStatus]) ? $breakdownStatusMap[$newStatus] : 'Pending';
        
        // Determine if breakdowns should be marked as paid
        // Mark as paid if status is Paid, Completed, Partially Paid, or Fully Paid
        $isPaid = ($newStatus === 'Paid' || 
                   $newStatus === 'Completed' || 
                   $newStatus === 'Partially Paid' || 
                   $newStatus === 'Fully Paid') ? 1 : 0;
        
        // Update ONLY the payment_breakdowns linked to this payment_id AND is_selected = 1
        // CRITICAL: Only update periods that were actually selected for payment (is_selected = 1)
        // Example: Booking 16 has 2 periods both with payment_id = 19:
        //   - Period 1: is_selected = 1 (this was paid) → UPDATE to is_paid = 1
        //   - Period 2: is_selected = 0 (not selected) → REMAIN is_paid = 0
        $breakdownUpdateSql = "UPDATE payment_breakdowns 
                              SET payment_status = :breakdown_status,
                                  is_paid = :is_paid,
                                  updated_at = NOW()
                              WHERE payment_id = :payment_id
                              AND is_selected = 1";
        
        $breakdownUpdateStmt = $pdo->prepare($breakdownUpdateSql);
        $breakdownUpdateStmt->execute([
            ':breakdown_status' => $breakdownStatus,
            ':is_paid' => $isPaid,
            ':payment_id' => $paymentId
        ]);
        
        // Also handle status changes for non-selected periods (if marking as Pending/Overdue/Cancelled)
        // Non-selected periods should also reflect the payment status change
        if ($newStatus !== 'Paid' && 
            $newStatus !== 'Completed' && 
            $newStatus !== 'Partially Paid' && 
            $newStatus !== 'Fully Paid') {
            $breakdownUpdateAllSql = "UPDATE payment_breakdowns 
                                     SET payment_status = :breakdown_status,
                                         updated_at = NOW()
                                     WHERE payment_id = :payment_id
                                     AND is_selected = 0";
            
            $breakdownUpdateAllStmt = $pdo->prepare($breakdownUpdateAllSql);
            $breakdownUpdateAllStmt->execute([
                ':breakdown_status' => $breakdownStatus,
                ':payment_id' => $paymentId
            ]);
        }
        
        // Log breakdown update (minimal logging during transaction to speed up response)
        // Detailed logging will happen after response is sent
        if (function_exists('error_log')) {
            $affectedBreakdowns = $breakdownUpdateStmt->rowCount();
            error_log("Updated $affectedBreakdowns breakdown(s) for payment_id $paymentId");
        }
        
        // AUTO-UPDATE payment_status based on breakdown payment progress
        // BREAKDOWNS ARE THE SOURCE OF TRUTH - Calculate final status from breakdowns
        // IMPORTANT: Only auto-calculate if we're actually marking as paid (not for Pending/Overdue/Cancelled)
        // This prevents premature status changes when booking is created
        
        $shouldAutoCalculate = ($newStatus === 'Paid' || 
                                $newStatus === 'Completed' || 
                                $newStatus === 'Partially Paid' || 
                                $newStatus === 'Fully Paid');
        
        $finalPaymentStatus = $newStatus; // Default to manual status
        
        if ($shouldAutoCalculate) {
            // Get booking_id from this payment
            $bookingStmt = $pdo->prepare("SELECT booking_id FROM payments WHERE payment_id = :payment_id");
            $bookingStmt->execute([':payment_id' => $paymentId]);
            $bookingResult = $bookingStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($bookingResult && !empty($bookingResult['booking_id'])) {
                $bookingId = intval($bookingResult['booking_id']);
                
                // Calculate payment progress for ALL breakdowns in this booking
                // CRITICAL: Only count periods where is_paid = 1 (not just payment_id IS NOT NULL)
                // When booking is created, periods may have payment_id but is_paid = 0
                $progressStmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_periods,
                        SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_periods
                    FROM payment_breakdowns
                    WHERE booking_id = :booking_id
                ");
                $progressStmt->execute([':booking_id' => $bookingId]);
                $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
                
                $totalPeriods = intval($progress['total_periods']);
                $paidPeriods = intval($progress['paid_periods']);
                
                // Determine payment status based on breakdown progress (SOURCE OF TRUTH)
                // Status mapping based on new enum: 'Pending','Partially Paid','Fully Paid','Failed','Refunded'
                // - No periods paid (0/X) → 'Pending'
                // - Some periods paid but not all (X/Y where X < Y) → 'Partially Paid'
                // - All periods paid (X/X) → 'Fully Paid'
                $finalPaymentStatus = 'Pending'; // Default
                
                if ($totalPeriods > 0) {
                    if ($paidPeriods >= $totalPeriods) {
                        // All periods paid = Fully Paid
                        $finalPaymentStatus = 'Fully Paid';
                    } elseif ($paidPeriods > 0) {
                        // Some periods paid but not all = Partial Payment
                        $finalPaymentStatus = 'Partially Paid';
                    } else {
                        // No periods paid = Pending
                        $finalPaymentStatus = 'Pending';
                    }
                }
                
                // Logging moved to after response is sent
                
                // Update ALL payments for this booking with the calculated status
                // This ensures consistency across all payments for the same booking
                // The calculated status from breakdowns is the FINAL status
                $updateAllPaymentsStmt = $pdo->prepare("
                    UPDATE payments 
                    SET payment_status = :final_status,
                        updated_at = NOW()
                    WHERE booking_id = :booking_id
                ");
                $updateAllPaymentsStmt->execute([
                    ':final_status' => $finalPaymentStatus,
                    ':booking_id' => $bookingId
                ]);
                
                // Update the status variable for response
                $newStatus = $finalPaymentStatus;
                
                // Store for logging after response (use variables that will be in scope)
                $logAffectedPayments = $updateAllPaymentsStmt->rowCount();
                $logPaymentProgress = "$paidPeriods/$totalPeriods";
                $logBookingId = $bookingId;
                $logFinalStatus = $finalPaymentStatus;
            } else {
                // No booking_id found - use manual status (fallback for edge cases)
                $updateStmt = $pdo->prepare("UPDATE payments SET payment_status = :status, updated_at = NOW() WHERE payment_id = :payment_id");
                $updateStmt->execute([
                    ':status' => $newStatus,
                    ':payment_id' => $paymentId
                ]);
            }
        } else {
            // For non-paid statuses (Pending, Overdue, Cancelled, etc.), use manual status
            // Don't auto-calculate - just update with the manual status
            $updateStmt = $pdo->prepare("UPDATE payments SET payment_status = :status, updated_at = NOW() WHERE payment_id = :payment_id");
            $updateStmt->execute([
                ':status' => $newStatus,
                ':payment_id' => $paymentId
            ]);
        }
        
        // If status is 'Partially Paid' or 'Fully Paid', also update booking status if linked
        $bookingIdForActiveBoarders = null;
        $bookingStatusBeforeUpdate = null;
        
        if ($newStatus === 'Partially Paid' || $newStatus === 'Fully Paid' || $newStatus === 'Completed') {
            // Get booking info BEFORE updating to check if it's still Pending
            $getBookingInfoStmt = $pdo->prepare("
                SELECT b.booking_id, b.user_id, b.booking_status, 
                       ru.room_id, bhr.bh_id as boarding_house_id
                FROM bookings b
                INNER JOIN payments p ON b.booking_id = p.booking_id
                INNER JOIN room_units ru ON b.room_id = ru.room_id
                INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                WHERE p.payment_id = :payment_id
                AND b.booking_status != 'Cancelled'
            ");
            $getBookingInfoStmt->execute([':payment_id' => $paymentId]);
            $bookingInfo = $getBookingInfoStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($bookingInfo) {
                $bookingIdForActiveBoarders = intval($bookingInfo['booking_id']);
                $bookingStatusBeforeUpdate = $bookingInfo['booking_status'];
                
                // Update booking status to Confirmed
                $bookingUpdateStmt = $pdo->prepare("
                    UPDATE bookings b
                    INNER JOIN payments p ON b.booking_id = p.booking_id
                    SET b.booking_status = 'Confirmed'
                    WHERE p.payment_id = :payment_id
                    AND b.booking_status != 'Cancelled'
                ");
                $bookingUpdateStmt->execute([':payment_id' => $paymentId]);
            }
        }
        
        // Add to active_boarders if payment is marked as paid AND booking is still Pending
        // This ensures synchronous behavior: marking payment as paid = adding to active_boarders
        // BUT only if booking hasn't been approved/confirmed yet (only add once)
        if (($newStatus === 'Partially Paid' || $newStatus === 'Fully Paid' || $newStatus === 'Completed') 
            && $bookingInfo 
            && $bookingStatusBeforeUpdate === 'Pending') {
            
            $boarderUserId = intval($bookingInfo['user_id']);
            $roomId = intval($bookingInfo['room_id']);
            $boardingHouseId = intval($bookingInfo['boarding_house_id']);
            
            if (function_exists('error_log')) {
                error_log("update_payment_status.php - Processing active_boarders for payment_id: $paymentId, booking_id: $bookingIdForActiveBoarders");
                error_log("update_payment_status.php - user_id=$boarderUserId, room_id=$roomId, boarding_house_id=$boardingHouseId");
            }
            
            // Check if boarder already exists in active_boarders for this exact room and boarding house
            $checkActiveSql = "
                SELECT active_id, status 
                FROM active_boarders 
                WHERE user_id = :user_id 
                AND room_id = :room_id 
                AND boarding_house_id = :boarding_house_id
            ";
            $checkActiveStmt = $pdo->prepare($checkActiveSql);
            $checkActiveStmt->execute([
                ':user_id' => $boarderUserId,
                ':room_id' => $roomId,
                ':boarding_house_id' => $boardingHouseId
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
                    error_log("update_payment_status.php - Updated existing active_boarders record (active_id: {$existingActive['active_id']}) to Active status");
                }
            } else {
                // Insert new record into active_boarders
                // This happens when payment is marked as paid and booking is still Pending
                $insertActiveSql = "
                    INSERT INTO active_boarders (user_id, status, room_id, boarding_house_id) 
                    VALUES (:user_id, 'Active', :room_id, :boarding_house_id)
                ";
                $insertActiveStmt = $pdo->prepare($insertActiveSql);
                $insertActiveStmt->execute([
                    ':user_id' => $boarderUserId,
                    ':room_id' => $roomId,
                    ':boarding_house_id' => $boardingHouseId
                ]);
                $activeId = $pdo->lastInsertId();
                
                if (function_exists('error_log')) {
                    error_log("update_payment_status.php - Successfully inserted new active_boarders record (active_id: $activeId) for user_id: $boarderUserId, room_id: $roomId, boarding_house_id: $boardingHouseId");
                }
                
                if ($activeId == 0) {
                    // Insert failed - log error but don't fail the transaction
                    if (function_exists('error_log')) {
                        error_log("update_payment_status.php - WARNING: Failed to insert into active_boarders - lastInsertId is 0");
                    }
                }
            }
        } else if ($bookingInfo && $bookingStatusBeforeUpdate !== 'Pending') {
            // Booking is already Confirmed/Approved - don't add to active_boarders (already added when approved)
            if (function_exists('error_log')) {
                error_log("update_payment_status.php - Skipping active_boarders insert/update - booking is already " . $bookingStatusBeforeUpdate . " (only add once)");
            }
        }
        
        // Commit transaction FIRST - this is the critical operation
        $pdo->commit();
        
        // Prepare response data IMMEDIATELY - before any logging or other processing
        $responseData = [
            'success' => true,
            'message' => 'Payment status updated successfully',
            'status' => $newStatus,
            'should_refresh' => true, // Flag to tell frontend to refresh/navigate back
            'navigate_back' => true,  // Flag to tell frontend to navigate back to previous screen
            'data' => [
                'payment_id' => $paymentId,
                'old_status' => $payment['payment_status'],
                'new_status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        // Send response IMMEDIATELY to prevent timeout - do this BEFORE any logging or notifications
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
        
        // NOW do detailed logging and other non-critical operations AFTER response is sent
        if (function_exists('error_log')) {
            error_log("Payment status updated successfully - payment_id: $paymentId, status: $newStatus");
            
            // Log detailed breakdown info if available
            if (isset($logPaymentProgress) && isset($logAffectedPayments)) {
                error_log("Auto-updated $logAffectedPayments payment(s) for booking $logBookingId to status: $logFinalStatus ($logPaymentProgress periods paid)");
            }
        }
        
        // Send notifications AFTER response is sent (non-blocking)
        // This prevents timeout issues with slow FCM calls
        try {
            require_once 'activity_notifications.php';
            
            // Get payment details for notifications
            $paymentDetailsSql = "
                SELECT 
                    p.payment_id,
                    p.booking_id,
                    p.payment_amount,
                    p.payment_status,
                    p.payment_method,
                    b.user_id as boarder_user_id,
                    ru.room_id,
                    bhr.room_name,
                    bh.bh_id,
                    bh.user_id as owner_user_id,
                    bh.bh_name
                FROM payments p
                JOIN bookings b ON p.booking_id = b.booking_id
                JOIN room_units ru ON b.room_id = ru.room_id
                JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE p.payment_id = ?
            ";
            $paymentDetailsStmt = $pdo->prepare($paymentDetailsSql);
            $paymentDetailsStmt->execute([$paymentId]);
            $paymentDetails = $paymentDetailsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($paymentDetails) {
                $boarderUserId = intval($paymentDetails['boarder_user_id']);
                $ownerUserId = intval($paymentDetails['owner_user_id']);
                $paymentAmount = floatval($paymentDetails['payment_amount']);
                $oldStatus = $payment['payment_status'];
                
                error_log("Sending payment notifications - boarder_user_id: $boarderUserId, owner_user_id: $ownerUserId, status: $oldStatus -> $newStatus");
                
                // Determine which notifications to send based on status change
                $isPaidStatus = in_array($newStatus, ['Paid', 'Completed', 'Partially Paid', 'Fully Paid']);
                
                // CRITICAL: Only send ONE notification per user to prevent duplicates
                // Boarder gets notification about their payment status
                // Owner gets notification about receiving payment
                // These are DIFFERENT users, so each gets ONE notification
                
                if ($boarderUserId > 0 && $boarderUserId != $ownerUserId) {
                    // Boarder is different from owner - send boarder notification
                    if ($newStatus === 'Overdue') {
                        // Special case: Overdue notification
                        // Check for duplicate before sending
                        $checkDuplicateSql = "
                            SELECT notif_id 
                            FROM notifications 
                            WHERE user_id = ? 
                            AND notif_type = 'payment' 
                            AND notif_title = 'Payment Overdue'
                            AND notif_message LIKE ?
                            AND notif_created_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
                            LIMIT 1
                        ";
                        $checkDuplicateStmt = $pdo->prepare($checkDuplicateSql);
                        $messagePattern = "%₱" . number_format($paymentAmount, 2) . "%";
                        $checkDuplicateStmt->execute([$boarderUserId, $messagePattern]);
                        $duplicate = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$duplicate) {
                            ActivityNotifications::notifyPaymentOverdue($boarderUserId, [
                                'amount' => $paymentAmount,
                                'payment_id' => $paymentId,
                                'room_name' => $paymentDetails['room_name'] ?? 'room'
                            ]);
                            error_log("Notification sent to boarder (user_id: $boarderUserId) about overdue payment");
                        } else {
                            error_log("Duplicate notification prevented for boarder (user_id: $boarderUserId) - payment_id: $paymentId (overdue notification already sent within last 10 seconds)");
                        }
                    } else {
                        // All other status changes - notify boarder
                        // Check for duplicate before sending
                        $checkDuplicateSql = "
                            SELECT notif_id 
                            FROM notifications 
                            WHERE user_id = ? 
                            AND notif_type = 'payment' 
                            AND notif_title = 'Payment Status Updated'
                            AND notif_message LIKE ?
                            AND notif_created_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
                            LIMIT 1
                        ";
                        $checkDuplicateStmt = $pdo->prepare($checkDuplicateSql);
                        $messagePattern = "%₱" . number_format($paymentAmount, 2) . "%" . ucfirst($newStatus) . "%";
                        $checkDuplicateStmt->execute([$boarderUserId, $messagePattern]);
                        $duplicate = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$duplicate) {
                            ActivityNotifications::notifyPaymentStatusUpdated($boarderUserId, [
                                'status' => $newStatus,
                                'amount' => $paymentAmount,
                                'payment_id' => $paymentId,
                                'room_name' => $paymentDetails['room_name'] ?? 'room',
                                'old_status' => $oldStatus
                            ]);
                            error_log("Notification sent to boarder (user_id: $boarderUserId) about payment status update to: $newStatus");
                        } else {
                            error_log("Duplicate notification prevented for boarder (user_id: $boarderUserId) - payment_id: $paymentId (status update notification already sent within last 10 seconds)");
                        }
                    }
                }
                
                // Notify owner - only when payment is received/confirmed (Paid, Completed, etc.)
                // AND owner is different from boarder (to prevent duplicate notifications)
                // CRITICAL: Check for duplicate notification before sending
                if ($ownerUserId > 0 && $ownerUserId != $boarderUserId && $isPaidStatus) {
                    // Check if we already sent a "Payment Received" notification for this payment in the last 10 seconds
                    // This prevents duplicate notifications from double-clicks or race conditions
                    $checkDuplicateSql = "
                        SELECT notif_id 
                        FROM notifications 
                        WHERE user_id = ? 
                        AND notif_type = 'payment' 
                        AND notif_title = 'Payment Received'
                        AND (notif_message LIKE ? OR notif_message LIKE ?)
                        AND notif_created_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
                        LIMIT 1
                    ";
                    $checkDuplicateStmt = $pdo->prepare($checkDuplicateSql);
                    $amountFormatted = number_format($paymentAmount, 2);
                    $messagePattern1 = "%₱" . $amountFormatted . "%";
                    $messagePattern2 = "%Payment of ₱" . $amountFormatted . "%";
                    $checkDuplicateStmt->execute([$ownerUserId, $messagePattern1, $messagePattern2]);
                    $duplicate = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$duplicate) {
                        // No duplicate found - safe to send notification
                        ActivityNotifications::notifyPaymentReceived($ownerUserId, [
                            'amount' => $paymentAmount,
                            'description' => 'Payment for ' . ($paymentDetails['room_name'] ?? 'room') . ' at ' . ($paymentDetails['bh_name'] ?? 'boarding house'),
                            'payment_id' => $paymentId,
                            'boarder_user_id' => $boarderUserId,
                            'status' => $newStatus
                        ]);
                        error_log("Notification sent to owner (user_id: $ownerUserId) about payment received");
                    } else {
                        error_log("Duplicate notification prevented for owner (user_id: $ownerUserId) - payment_id: $paymentId (notification already sent within last 10 seconds)");
                    }
                } elseif ($ownerUserId > 0 && $ownerUserId == $boarderUserId) {
                    // Edge case: Owner and boarder are the same person - only send one notification
                    error_log("Note: Owner and boarder are the same user (user_id: $ownerUserId) - skipping duplicate notification");
                }
            } else {
                error_log("Warning: Could not fetch payment details for notification - payment_id: $paymentId");
            }
        } catch (Exception $e) {
            // Don't fail the payment update if notification fails
            error_log("Warning: Failed to send payment notifications: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        // Rollback transaction
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log to error log (not output)
        if (function_exists('error_log')) {
        error_log("Error updating payment status: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        }
        
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update payment status: ' . $e->getMessage()
        ]);
        ob_end_flush();
    }
    
} catch (PDOException $e) {
    // Log to error log (not output)
    if (function_exists('error_log')) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    ob_end_flush();
    
} catch (Exception $e) {
    // Log to error log (not output)
    if (function_exists('error_log')) {
    error_log("General error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}



