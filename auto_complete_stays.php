<?php
/**
 * Auto-complete stays when checkout date has passed
 * This script should be run via cron job daily to automatically move completed stays to history
 * 
 * Usage: php auto_complete_stays.php
 * Or set up cron: 0 0 * * * php /path/to/auto_complete_stays.php
 */

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Find all bookings that should be marked as Completed
    // Bookings with end_date < today (not including today - last day should still show as active)
    // Only complete stays where checkout date has PASSED (yesterday or earlier)
    // Check for any active status (Confirmed, Approved, or any status that's not Completed/Cancelled)
    $sql = "
        SELECT 
            b.booking_id,
            b.user_id,
            b.room_id,
            b.end_date,
            b.booking_status
        FROM bookings b
        WHERE b.booking_status NOT IN ('Completed', 'Cancelled')
            AND b.end_date IS NOT NULL
            AND DATE(b.end_date) < CURDATE()
    ";
    
    $stmt = $pdo->query($sql);
    $bookingsToComplete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $completedCount = 0;
    $removedFromActiveCount = 0;
    $orphanedRemoved = 0;
    
    foreach ($bookingsToComplete as $booking) {
        $bookingId = $booking['booking_id'];
        $userId = $booking['user_id'];
        $roomId = $booking['room_id'];
        
        // Update booking status to Completed
        $updateBookingSql = "
            UPDATE bookings 
            SET booking_status = 'Completed'
            WHERE booking_id = ?
        ";
        $updateStmt = $pdo->prepare($updateBookingSql);
        $updateStmt->execute([$bookingId]);
        
        if ($updateStmt->rowCount() > 0) {
            $completedCount++;
            error_log("auto_complete_stays.php - Marked booking $bookingId as Completed (end_date: {$booking['end_date']})");
        }
        
        // Remove from active_boarders if exists
        $removeActiveSql = "
            DELETE FROM active_boarders
            WHERE user_id = ? 
                AND room_id = ?
                AND status = 'Active'
        ";
        $removeStmt = $pdo->prepare($removeActiveSql);
        $removeStmt->execute([$userId, $roomId]);
        
        if ($removeStmt->rowCount() > 0) {
            $removedFromActiveCount++;
            error_log("auto_complete_stays.php - Removed user $userId from active_boarders (room_id: $roomId)");
        }
    }
    
    // Also clean up orphaned active_boarders records
    // Remove any active_boarders entries where the corresponding booking is Completed or Cancelled
    // or where the booking's end_date has passed
    $cleanupOrphanedSql = "
        DELETE ab FROM active_boarders ab
        LEFT JOIN bookings b ON ab.user_id = b.user_id 
            AND ab.room_id = b.room_id
            AND b.booking_status NOT IN ('Completed', 'Cancelled')
            AND (b.end_date IS NULL OR DATE(b.end_date) >= CURDATE())
        WHERE ab.status = 'Active'
            AND b.booking_id IS NULL
    ";
    
    $cleanupStmt = $pdo->prepare($cleanupOrphanedSql);
    $cleanupStmt->execute();
    $orphanedRemoved = $cleanupStmt->rowCount();
    
    if ($orphanedRemoved > 0) {
        $removedFromActiveCount += $orphanedRemoved;
        error_log("auto_complete_stays.php - Removed $orphanedRemoved orphaned records from active_boarders");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log summary
    $summary = "auto_complete_stays.php - Completed: $completedCount bookings, Removed: $removedFromActiveCount from active_boarders";
    if ($orphanedRemoved > 0) {
        $summary .= " (including $orphanedRemoved orphaned records)";
    }
    error_log($summary);
    
    // If running from command line, output summary
    if (php_sapi_name() === 'cli') {
        echo $summary . "\n";
    }
    
    // Return JSON response if called via HTTP
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode([
            'success' => true,
            'message' => $summary,
            'completed_bookings' => $completedCount,
            'removed_from_active' => $removedFromActiveCount,
            'orphaned_removed' => $orphanedRemoved
        ]);
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in auto_complete_stays.php: " . $e->getMessage());
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in auto_complete_stays.php: " . $e->getMessage());
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error: ' . $e->getMessage()
        ]);
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>

