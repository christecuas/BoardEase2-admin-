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
    // Also get room category and capacity for proper room status update
    $sql = "
        SELECT 
            b.booking_id,
            b.user_id,
            b.room_id,
            b.end_date,
            b.booking_status,
            bhr.room_category,
            bhr.capacity
        FROM bookings b
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        WHERE b.booking_status NOT IN ('Completed', 'Cancelled')
            AND b.end_date IS NOT NULL
            AND DATE(b.end_date) < CURDATE()
    ";
    
    $stmt = $pdo->query($sql);
    $bookingsToComplete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $completedCount = 0;
    $removedFromActiveCount = 0;
    $orphanedRemoved = 0;
    $roomsUpdatedCount = 0;
    
    foreach ($bookingsToComplete as $booking) {
        $bookingId = $booking['booking_id'];
        $userId = $booking['user_id'];
        $roomId = $booking['room_id'];
        $roomCategory = $booking['room_category'];
        $capacity = intval($booking['capacity']);
        
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
        
        // Update room status based on room category
        if ($roomCategory === 'Private Room') {
            // Private Room: Always set to 'Available' when booking is completed
            $updateRoomSql = "
                UPDATE room_units 
                SET status = 'Available' 
                WHERE room_id = ?
            ";
            $updateRoomStmt = $pdo->prepare($updateRoomSql);
            $updateRoomStmt->execute([$roomId]);
            
            if ($updateRoomStmt->rowCount() > 0) {
                $roomsUpdatedCount++;
                error_log("auto_complete_stays.php - Updated room $roomId (Private Room) to 'Available'");
            }
            
        } else if ($roomCategory === 'Bed Spacer') {
            // Bed Spacer: Check if there are other active bookings
            // If no other active bookings, set to 'Available'
            // If there are still active bookings, check capacity
            $checkActiveBookingsSql = "
                SELECT COUNT(b2.booking_id) as active_count
                FROM bookings b2
                WHERE b2.room_id = ?
                    AND b2.booking_status IN ('Pending', 'Confirmed')
                    AND b2.booking_id != ?
            ";
            $checkActiveStmt = $pdo->prepare($checkActiveBookingsSql);
            $checkActiveStmt->execute([$roomId, $bookingId]);
            $activeResult = $checkActiveStmt->fetch(PDO::FETCH_ASSOC);
            $activeBookingsCount = intval($activeResult['active_count']);
            
            if ($activeBookingsCount == 0) {
                // No other active bookings, set to 'Available'
                $updateRoomSql = "
                    UPDATE room_units 
                    SET status = 'Available' 
                    WHERE room_id = ?
                ";
                $updateRoomStmt = $pdo->prepare($updateRoomSql);
                $updateRoomStmt->execute([$roomId]);
                
                if ($updateRoomStmt->rowCount() > 0) {
                    $roomsUpdatedCount++;
                    error_log("auto_complete_stays.php - Updated room $roomId (Bed Spacer) to 'Available' (no other active bookings)");
                }
            } else {
                // There are still active bookings, check if room should be 'Occupied' or 'Available'
                if ($activeBookingsCount >= $capacity) {
                    // Still at full capacity, keep as 'Occupied'
                    $updateRoomSql = "
                        UPDATE room_units 
                        SET status = 'Occupied' 
                        WHERE room_id = ?
                    ";
                    $updateRoomStmt = $pdo->prepare($updateRoomSql);
                    $updateRoomStmt->execute([$roomId]);
                    
                    if ($updateRoomStmt->rowCount() > 0) {
                        error_log("auto_complete_stays.php - Room $roomId (Bed Spacer) remains 'Occupied' ($activeBookingsCount/$capacity beds still occupied)");
                    }
                } else {
                    // Has available capacity, set to 'Available'
                    $updateRoomSql = "
                        UPDATE room_units 
                        SET status = 'Available' 
                        WHERE room_id = ?
                    ";
                    $updateRoomStmt = $pdo->prepare($updateRoomSql);
                    $updateRoomStmt->execute([$roomId]);
                    
                    if ($updateRoomStmt->rowCount() > 0) {
                        $roomsUpdatedCount++;
                        error_log("auto_complete_stays.php - Updated room $roomId (Bed Spacer) to 'Available' ($activeBookingsCount/$capacity beds occupied)");
                    }
                }
            }
        } else {
            // Unknown category - use Private Room logic (set to Available)
            $updateRoomSql = "
                UPDATE room_units 
                SET status = 'Available' 
                WHERE room_id = ?
            ";
            $updateRoomStmt = $pdo->prepare($updateRoomSql);
            $updateRoomStmt->execute([$roomId]);
            
            if ($updateRoomStmt->rowCount() > 0) {
                $roomsUpdatedCount++;
                error_log("auto_complete_stays.php - Updated room $roomId (Unknown category) to 'Available'");
            }
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
    $summary = "auto_complete_stays.php - Completed: $completedCount bookings, Removed: $removedFromActiveCount from active_boarders, Updated: $roomsUpdatedCount rooms to Available";
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
            'orphaned_removed' => $orphanedRemoved,
            'rooms_updated' => $roomsUpdatedCount
        ]);
    }
    
} catch (PDOException $e) {
    // Check if $pdo exists and is in transaction before rolling back
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $errorMessage = "Database error in auto_complete_stays.php: " . $e->getMessage();
    
    // Check if it's a connection error
    if (strpos($e->getMessage(), 'refused') !== false || 
        strpos($e->getMessage(), 'Connection refused') !== false ||
        strpos($e->getMessage(), 'target machine actively refused') !== false) {
        $errorMessage .= " - MySQL/XAMPP may not be running. Please start MySQL service.";
    }
    
    error_log($errorMessage);
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
    } else {
        echo "Error: " . $errorMessage . "\n";
        if (strpos($e->getMessage(), 'refused') !== false) {
            echo "TIP: Make sure MySQL/XAMPP is running before running this script.\n";
        }
    }
} catch (Exception $e) {
    // Check if $pdo exists and is in transaction before rolling back
    if (isset($pdo) && $pdo->inTransaction()) {
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

