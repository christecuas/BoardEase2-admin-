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

// Clear any previous output
ob_clean();

// Database configuration
require_once 'dbConfig.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data === null) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        ob_end_flush();
        exit;
    }
    
    $bookingId = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
    $userId = isset($data['user_id']) ? intval($data['user_id']) : 0; // Boarder User ID
    $reason = isset($data['reason']) ? trim($data['reason']) : 'Cancelled by boarder';
    
    if ($bookingId == 0 || $userId == 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Booking ID and User ID are required']);
        ob_end_flush();
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify booking belongs to boarder
    $verifySql = "
        SELECT 
            b.booking_id, 
            b.booking_status, 
            b.user_id as boarder_user_id,
            b.room_id,
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
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        ob_end_flush();
        exit;
    }
    
    if ($booking['boarder_user_id'] != $userId) {
        $pdo->rollBack();
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Unauthorized: This booking does not belong to you']);
        ob_end_flush();
        exit;
    }
    
    // Check status
    if ($booking['booking_status'] != 'Pending' && $booking['booking_status'] != 'Approved') {
        $pdo->rollBack();
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Booking status is ' . $booking['booking_status'] . ' and cannot be cancelled.']);
        ob_end_flush();
        exit;
    }
    
    // Update booking status
    $updateSql = "UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = :booking_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([':booking_id' => $bookingId]);
    
    // Update room status (Restore availability)
    $roomId = $booking['room_id'];
    $roomCategory = $booking['room_category'] ?? '';
    
    if ($roomCategory === 'Private Room') {
        $pdo->prepare("UPDATE room_units SET status = 'Available' WHERE room_id = ?")->execute([$roomId]);
    } else if ($roomCategory === 'Bed Spacer') {
        // Simple logic for bedspacer: if it was full/Occupied, check if it should become Partially Occupied or Available
        // Re-evaluating capacity is complex, but generally freeing up a slot makes it at least 'Partially Occupied' or 'Available'
        // For simplicity: Set to 'Available' if count of confirmed bookings < capacity. 
        // Or trigger a script to recalculate status.
        // Let's use the robust logic from decline_booking.php (simplified here for brevity/reliability)
        
        // Count confirmed active bookings
        $countSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Confirmed'";
        $cStmt = $pdo->prepare($countSql);
        $cStmt->execute([$roomId]);
        $confirmedCount = $cStmt->fetchColumn();
        
        // Count pending active bookings
        $pSql = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status IN ('Pending', 'Approved')";
        $pStmt = $pdo->prepare($pSql);
        $pStmt->execute([$roomId]);
        $pendingCount = $pStmt->fetchColumn(); 
        
        // Note: The CURRENT booking is already 'Cancelled' so it's not counted above
        $capacity = intval($booking['capacity']);
        
        $newStatus = 'Available';
        if ($confirmedCount >= $capacity) {
            $newStatus = 'Occupied';
        } else if ($confirmedCount > 0 || $pendingCount > 0) {
            $newStatus = 'Partially Occupied';
        }
        
        $pdo->prepare("UPDATE room_units SET status = ? WHERE room_id = ?")->execute([$newStatus, $roomId]);
    }
    
    // Update payment status if exists
    $pdo->prepare("UPDATE payments SET payment_status = 'Cancelled' WHERE booking_id = ?")->execute([$bookingId]);
    
    $pdo->commit();
    
    // Notifications (Optional: Notify Owner)
    // ... code to notify owner ...
    
    ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => 'Booking cancelled successfully',
        'status' => 'Cancelled',
        'booking_id' => $bookingId
    ]);
    ob_end_flush();
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in cancel_booking.php: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    ob_end_flush();
}
?>
