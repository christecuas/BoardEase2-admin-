<?php
/**
 * Auto-complete stays when checkout date has passed
 * This script should be run via cron job daily to automatically move completed stays to history
 * 
 * Usage: php auto_complete_stays.php
 * Or set up cron: 0 0 * * * php /path/to/auto_complete_stays.php
 */

// Database configuration
// Database configuration
require_once 'dbConfig.php';

// define('DB_HOST', '');
// define('DB_USER', 'u223444398_userboardease');
// define('DB_PASS', '!Boardease2026');
// define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/auto_complete_stays.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        echo $message . "\n";
    }
}

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
            bhr.capacity,
            bh.user_id as owner_id
        FROM bookings b
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
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
            logMessage("Marked booking $bookingId as Completed (end_date: {$booking['end_date']})");
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
            logMessage("Removed user $userId from active_boarders (room_id: $roomId)");
        }
        
        // Remove from Group Chat
        // Find groups owned by this BH Owner and remove the user
        $ownerId = $booking['owner_id'];
        if ($ownerId) {
            $getGroupsSql = "SELECT gc_id FROM chat_groups WHERE gc_created_by = ?";
            $groupStmt = $pdo->prepare($getGroupsSql);
            $groupStmt->execute([$ownerId]);
            $groups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($groups as $group) {
                $gcId = $group['gc_id'];
                $removeGroupMemberSql = "DELETE FROM group_members WHERE user_id = ? AND gc_id = ?";
                $delGroupStmt = $pdo->prepare($removeGroupMemberSql);
                $delGroupStmt->execute([$userId, $gcId]);
                
                if ($delGroupStmt->rowCount() > 0) {
                     logMessage("Removed user $userId from Group Chat $gcId (Stay Completed)");
                }
            }
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
                logMessage("Updated room $roomId (Private Room) to 'Available'");
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
                    logMessage("Updated room $roomId (Bed Spacer) to 'Available' (no other active bookings)");
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
                        logMessage("Room $roomId (Bed Spacer) remains 'Occupied' ($activeBookingsCount/$capacity beds still occupied)");
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
                        logMessage("Updated room $roomId (Bed Spacer) to 'Available' ($activeBookingsCount/$capacity beds occupied)");
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
                logMessage("Updated room $roomId (Unknown category) to 'Available'");
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
        logMessage("Removed $orphanedRemoved orphaned records from active_boarders");
    }
    
    // SYNC GROUP MEMBERS: Remove users from Group Chat if they are NOT in active_boarders
    // This catches users removed via orphaned cleanup or any other manual removal
    // Logic: Delete from group_members IF:
    // 1. User is NOT the group owner (gc_created_by)
    // 2. User is NOT an active boarder in any BH owned by the group owner
    $syncGroupMembersSql = "
        DELETE gm FROM group_members gm
        JOIN chat_groups gc ON gm.gc_id = gc.gc_id
        LEFT JOIN (
            SELECT DISTINCT ab.user_id, bh.user_id as owner_id
            FROM active_boarders ab
            JOIN room_units ru ON ab.room_id = ru.room_id
            JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE ab.status = 'Active'
        ) active_status ON gm.user_id = active_status.user_id AND gc.gc_created_by = active_status.owner_id
        WHERE gm.user_id != gc.gc_created_by  -- Don't remove the owner
        AND active_status.user_id IS NULL       -- User is not active for this owner
    ";
    
    $syncStmt = $pdo->prepare($syncGroupMembersSql);
    $syncStmt->execute();
    $removedFromGroups = $syncStmt->rowCount();
    
    if ($removedFromGroups > 0) {
        logMessage("Removed $removedFromGroups non-active users from Group Chats (Sync Check)");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log summary
    $summary = "Completed: $completedCount bookings, Removed: $removedFromActiveCount from active_boarders, Updated: $roomsUpdatedCount rooms to Available";
    if ($orphanedRemoved > 0) {
        $summary .= " (including $orphanedRemoved orphaned records)";
    }
    if ($removedFromGroups > 0) {
        $summary .= ", Removed from Groups: $removedFromGroups";
    }
    logMessage($summary);
    
    // Clean up old logs (keep only last 7 days)
    $logDir = __DIR__ . '/logs';
    if (file_exists($logDir)) {
        $files = glob($logDir . '/auto_complete_*.log');
        $cutoff = time() - (7 * 24 * 60 * 60); // 7 days ago
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    logMessage("Deleted old log: " . basename($file));
                    $deletedCount++;
                } else {
                    logMessage("WARNING: Could not delete old log: " . basename($file));
                }
            }
        }
        
        if ($deletedCount > 0) {
            $cleanupMsg = "Cleaned up $deletedCount old log file(s)";
            logMessage($cleanupMsg);
        }
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
    
    $errorMessage = "Database error: " . $e->getMessage();
    
    // Check if it's a connection error
    if (strpos($e->getMessage(), 'refused') !== false || 
        strpos($e->getMessage(), 'Connection refused') !== false ||
        strpos($e->getMessage(), 'target machine actively refused') !== false) {
        $errorMessage .= " - MySQL/XAMPP may not be running. Please start MySQL service.";
    }
    
    logMessage("ERROR: " . $errorMessage);
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
    }
} catch (Exception $e) {
    // Check if $pdo exists and is in transaction before rolling back
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $errorMessage = "Error: " . $e->getMessage();
    logMessage($errorMessage);
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
    }
}
?>

