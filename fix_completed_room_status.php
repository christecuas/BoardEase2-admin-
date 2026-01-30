<?php
/**
 * Fix room status for bookings that are already Completed
 * This script updates room status to 'Available' for rooms that have Completed bookings
 * but the room status was not updated (because the old auto_complete_stays.php didn't update it)
 * 
 * Usage: php fix_completed_room_status.php
 */

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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Find all Completed bookings with their room info
    $sql = "
        SELECT 
            b.booking_id,
            b.room_id,
            bhr.room_category,
            bhr.capacity,
            ru.status as current_room_status
        FROM bookings b
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        WHERE b.booking_status = 'Completed'
            AND ru.status != 'Unavailable'
    ";
    
    $stmt = $pdo->query($sql);
    $completedBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $roomsUpdatedCount = 0;
    $roomsCheckedCount = 0;
    
    foreach ($completedBookings as $booking) {
        $bookingId = $booking['booking_id'];
        $roomId = $booking['room_id'];
        $roomCategory = $booking['room_category'];
        $capacity = intval($booking['capacity']);
        $currentStatus = $booking['current_room_status'];
        
        $roomsCheckedCount++;
        
        // Update room status based on room category
        if ($roomCategory === 'Private Room') {
            // Private Room: Should be 'Available' if booking is Completed
            if ($currentStatus !== 'Available') {
                $updateRoomSql = "
                    UPDATE room_units 
                    SET status = 'Available' 
                    WHERE room_id = ?
                ";
                $updateRoomStmt = $pdo->prepare($updateRoomSql);
                $updateRoomStmt->execute([$roomId]);
                
                if ($updateRoomStmt->rowCount() > 0) {
                    $roomsUpdatedCount++;
                    error_log("fix_completed_room_status.php - Updated room $roomId (Private Room) from '$currentStatus' to 'Available' (booking_id: $bookingId)");
                }
            }
            
        } else if ($roomCategory === 'Bed Spacer') {
            // Bed Spacer: Check if there are other active bookings
            $checkActiveBookingsSql = "
                SELECT COUNT(b2.booking_id) as active_count
                FROM bookings b2
                WHERE b2.room_id = ?
                    AND b2.booking_status IN ('Pending', 'Confirmed')
            ";
            $checkActiveStmt = $pdo->prepare($checkActiveBookingsSql);
            $checkActiveStmt->execute([$roomId]);
            $activeResult = $checkActiveStmt->fetch(PDO::FETCH_ASSOC);
            $activeBookingsCount = intval($activeResult['active_count']);
            
            if ($activeBookingsCount == 0) {
                // No active bookings, should be 'Available'
                if ($currentStatus !== 'Available') {
                    $updateRoomSql = "
                        UPDATE room_units 
                        SET status = 'Available' 
                        WHERE room_id = ?
                    ";
                    $updateRoomStmt = $pdo->prepare($updateRoomSql);
                    $updateRoomStmt->execute([$roomId]);
                    
                    if ($updateRoomStmt->rowCount() > 0) {
                        $roomsUpdatedCount++;
                        error_log("fix_completed_room_status.php - Updated room $roomId (Bed Spacer) from '$currentStatus' to 'Available' (no active bookings, booking_id: $bookingId)");
                    }
                }
            } else {
                // There are active bookings, check capacity
                if ($activeBookingsCount >= $capacity) {
                    // At full capacity, should be 'Occupied'
                    if ($currentStatus !== 'Occupied') {
                        $updateRoomSql = "
                            UPDATE room_units 
                            SET status = 'Occupied' 
                            WHERE room_id = ?
                        ";
                        $updateRoomStmt = $pdo->prepare($updateRoomSql);
                        $updateRoomStmt->execute([$roomId]);
                        
                        if ($updateRoomStmt->rowCount() > 0) {
                            $roomsUpdatedCount++;
                            error_log("fix_completed_room_status.php - Updated room $roomId (Bed Spacer) from '$currentStatus' to 'Occupied' ($activeBookingsCount/$capacity beds occupied, booking_id: $bookingId)");
                        }
                    }
                } else {
                    // Has available capacity, should be 'Available'
                    if ($currentStatus !== 'Available') {
                        $updateRoomSql = "
                            UPDATE room_units 
                            SET status = 'Available' 
                            WHERE room_id = ?
                        ";
                        $updateRoomStmt = $pdo->prepare($updateRoomSql);
                        $updateRoomStmt->execute([$roomId]);
                        
                        if ($updateRoomStmt->rowCount() > 0) {
                            $roomsUpdatedCount++;
                            error_log("fix_completed_room_status.php - Updated room $roomId (Bed Spacer) from '$currentStatus' to 'Available' ($activeBookingsCount/$capacity beds occupied, booking_id: $bookingId)");
                        }
                    }
                }
            }
        } else {
            // Unknown category - use Private Room logic (set to Available)
            if ($currentStatus !== 'Available') {
                $updateRoomSql = "
                    UPDATE room_units 
                    SET status = 'Available' 
                    WHERE room_id = ?
                ";
                $updateRoomStmt = $pdo->prepare($updateRoomSql);
                $updateRoomStmt->execute([$roomId]);
                
                if ($updateRoomStmt->rowCount() > 0) {
                    $roomsUpdatedCount++;
                    error_log("fix_completed_room_status.php - Updated room $roomId (Unknown category) from '$currentStatus' to 'Available' (booking_id: $bookingId)");
                }
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log summary
    $summary = "fix_completed_room_status.php - Checked: $roomsCheckedCount rooms with Completed bookings, Updated: $roomsUpdatedCount rooms";
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
            'rooms_checked' => $roomsCheckedCount,
            'rooms_updated' => $roomsUpdatedCount
        ]);
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in fix_completed_room_status.php: " . $e->getMessage());
    
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
    error_log("Error in fix_completed_room_status.php: " . $e->getMessage());
    
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

