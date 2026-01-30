<?php
// Script to fix missing active_boarders records for approved bookings
// This will backfill any approved bookings that don't have corresponding active_boarders entries

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

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
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Find all Confirmed bookings that don't have corresponding active_boarders entries
    $findMissingSql = "
        SELECT 
            b.booking_id,
            b.user_id as boarder_user_id,
            b.room_id,
            bh.bh_id as boarding_house_id
        FROM bookings b
        INNER JOIN room_units ru ON b.room_id = ru.room_id
        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        WHERE b.booking_status = 'Confirmed'
        AND NOT EXISTS (
            SELECT 1 
            FROM active_boarders ab 
            WHERE ab.user_id = b.user_id 
            AND ab.room_id = b.room_id
        )
        ORDER BY b.booking_id
    ";
    
    $findStmt = $pdo->prepare($findMissingSql);
    $findStmt->execute();
    $missingBookings = $findStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $inserted = 0;
    $updated = 0;
    $errors = array();
    
    foreach ($missingBookings as $booking) {
        try {
            $boarderUserId = $booking['boarder_user_id'];
            $roomId = $booking['room_id'];
            $boardingHouseId = $booking['boarding_house_id'];
            $bookingId = $booking['booking_id'];
            
            // Check if there's an existing active_boarders record for this user (maybe different room)
            $checkUserSql = "
                SELECT active_id, status, room_id
                FROM active_boarders 
                WHERE user_id = :user_id
            ";
            $checkUserStmt = $pdo->prepare($checkUserSql);
            $checkUserStmt->execute([':user_id' => $boarderUserId]);
            $existingForUser = $checkUserStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if there's an exact match (same user, room, and boarding house)
            $exactMatch = false;
            foreach ($existingForUser as $existing) {
                if ($existing['room_id'] == $roomId) {
                    // Update existing record to Active
                    $updateActiveSql = "
                        UPDATE active_boarders 
                        SET status = 'Active' 
                        WHERE active_id = :active_id
                    ";
                    $updateActiveStmt = $pdo->prepare($updateActiveSql);
                    $updateActiveStmt->execute([':active_id' => $existing['active_id']]);
                    $updated++;
                    $exactMatch = true;
                    error_log("fix_missing_active_boarders.php - Updated active_id: {$existing['active_id']} for booking_id: $bookingId");
                    break;
                }
            }
            
            if (!$exactMatch) {
                // Insert new record
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
                $inserted++;
                error_log("fix_missing_active_boarders.php - Inserted active_id: $activeId for booking_id: $bookingId");
            }
        } catch (Exception $e) {
            $errors[] = "Error processing booking_id {$booking['booking_id']}: " . $e->getMessage();
            error_log("fix_missing_active_boarders.php - Error for booking_id {$booking['booking_id']}: " . $e->getMessage());
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(array(
        'success' => true,
        'message' => 'Fixed missing active_boarders records',
        'total_missing' => count($missingBookings),
        'inserted' => $inserted,
        'updated' => $updated,
        'errors' => $errors
    ));
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in fix_missing_active_boarders.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in fix_missing_active_boarders.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

