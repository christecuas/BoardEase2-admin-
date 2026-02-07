<?php
// cron_check_expired_approvals.php
// This script should be run via cron job or scheduler every hour
// Cancels 'Approved' bookings that have not been paid within 24 hours
// Reverts room status from 'Reserved' to 'Available' or 'Available(Partially Occupied)'

require_once __DIR__ . '/dbConfig.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cron_errors.log');

try {
    // Use the DB constants from dbConfig.php
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Running auto-cancel check at " . date('Y-m-d H:i:s') . "\n";
    
    // Find expired bookings
    // Criteria: Status = 'Approved' and updated_at < (NOW - 24 hours)
    // Fallback: If approval_date is NULL, check updated_at (though approval_date should be set by approve_booking.php)
    
    $sql = "
        SELECT 
            b.booking_id, 
            b.room_id, 
            b.user_id, 
            bhr.room_category,
            b.updated_at
        FROM bookings b
        JOIN room_units ru ON b.room_id = ru.room_id
        JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        WHERE b.booking_status = 'Approved' 
        AND b.updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    
    $stmt = $pdo->query($sql);
    $expiredBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($expiredBookings) . " expired bookings.\n";
    
    foreach ($expiredBookings as $booking) {
        $bookingId = $booking['booking_id'];
        $roomId = $booking['room_id'];
        $category = $booking['room_category'];
        
        echo "Processing expired booking #$bookingId (Room: $roomId, Cat: $category)...\n";
        
        $pdo->beginTransaction();
        
        try {
            // 1. Cancel Booking
            // We use 'Cancelled' status with a specific rejection reason
            $cancelSql = "UPDATE bookings SET booking_status = 'Cancelled', rejection_reason = 'Payment period expired (24 hours)' WHERE booking_id = ?";
            $cancelStmt = $pdo->prepare($cancelSql);
            $cancelStmt->execute([$bookingId]);
            
            // 2. Revert Room Status
            if ($category === 'Private Room') {
                // Private Room -> Directly back to available
                // (Unless there's another Approved booking? Unlikely for Private Room as we block new ones, but getting overlapped is possible if bug)
                // Safe approach: Set to Available.
                $revertSql = "UPDATE room_units SET status = 'Available' WHERE room_id = ?";
                $pdo->prepare($revertSql)->execute([$roomId]);
                
            } else {
                // Bed Spacer logic
                // Check if any OTHER confirmed/approved bookings exist for this room
                
                 // Count confirmed active bookings
                 $cStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status IN ('Confirmed', 'Active')");
                 $cStmt->execute([$roomId]);
                 $confirmedCount = $cStmt->fetchColumn();
                 
                 // Count other approved bookings
                 $aStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND booking_status = 'Approved' AND booking_id != ?");
                 $aStmt->execute([$roomId, $bookingId]);
                 $otherApprovedCount = $aStmt->fetchColumn();
                 
                 if ($confirmedCount > 0 || $otherApprovedCount > 0) {
                     // Still has people -> 'Available(Partially Occupied)'
                     // Note: We assume it's NOT full because we just cancelled one.
                     $pdo->prepare("UPDATE room_units SET status = 'Available(Partially Occupied)' WHERE room_id = ?")->execute([$roomId]);
                 } else {
                     // Empty -> 'Available'
                     $pdo->prepare("UPDATE room_units SET status = 'Available' WHERE room_id = ?")->execute([$roomId]);
                 }
            }
            
            // 3. Cancel associated pending payments
            $cancelPaySql = "UPDATE payments SET payment_status = 'Cancelled' WHERE booking_id = ? AND payment_status = 'Pending'";
            $pdo->prepare($cancelPaySql)->execute([$bookingId]);
            
            $pdo->commit();
            echo "Cancelled successfully.\n";
            
            // Optional: Send notification to user about cancellation?
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Failed: " . $e->getMessage() . "\n";
            error_log("Failed to auto-cancel booking $bookingId: " . $e->getMessage());
        }
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
?>
