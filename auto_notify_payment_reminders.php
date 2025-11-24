<?php
/**
 * Auto Payment Reminder System
 * Sends payment reminders 5, 3, 2, 1 days before due date and on due date
 * Should be run daily at 10:00 AM via cron job or scheduled task
 * 
 * Usage: php auto_notify_payment_reminders.php
 * Or set up cron: 0 10 * * * php /path/to/auto_notify_payment_reminders.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/payment_reminders.log');

// Function to log to both error_log and stdout (for batch file capture)
function logMessage($message) {
    error_log($message);
    if (php_sapi_name() === 'cli') {
        echo $message . "\n";
    }
}

require_once 'db_helper.php';
require_once 'notification_helper.php';

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

try {
    // Create PDO connection for direct SQL queries
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get database helper for notification system
    $db = getDB();
    
    if (!$db) {
        throw new Exception("Failed to get database connection");
    }
    
    // Create payment_reminder_logs table if it doesn't exist
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS `payment_reminder_logs` (
          `log_id` int(11) NOT NULL AUTO_INCREMENT,
          `breakdown_id` int(11) NOT NULL COMMENT 'Payment breakdown ID',
          `user_id` int(11) NOT NULL COMMENT 'User who should receive the reminder',
          `reminder_type` enum('5_days_before','4_days_before','3_days_before','2_days_before','1_day_before','due_date') NOT NULL COMMENT 'Type of reminder',
          `due_date` date NOT NULL COMMENT 'Due date of the payment',
          `reminder_date` date NOT NULL COMMENT 'Date when reminder was sent',
          `notif_id` int(11) DEFAULT NULL COMMENT 'Notification ID created',
          `fcm_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether FCM push notification was sent',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`log_id`),
          UNIQUE KEY `unique_reminder` (`breakdown_id`, `reminder_type`, `reminder_date`),
          KEY `breakdown_id` (`breakdown_id`),
          KEY `user_id` (`user_id`),
          KEY `reminder_date` (`reminder_date`),
          KEY `due_date` (`due_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks payment reminder notifications to prevent duplicates'
    ";
    
    try {
        $pdo->exec($createTableSql);
        error_log("auto_notify_payment_reminders.php - payment_reminder_logs table created or already exists");
    } catch (PDOException $e) {
        // Table might already exist, that's okay
        if (strpos($e->getMessage(), 'already exists') === false) {
            error_log("auto_notify_payment_reminders.php - Warning creating table: " . $e->getMessage());
        }
    }
    
    $today = date('Y-m-d');
    $remindersSent = 0;
    $remindersFailed = 0;
    
    logMessage("=== Payment Reminder Check Started ===");
    logMessage("Date: $today");
    logMessage("Checking for payment breakdowns with due dates between today and 5 days from now...");
    
    // First, let's check what's actually in the database
    $checkSql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN pb.is_paid = 0 THEN 1 ELSE 0 END) as unpaid,
            SUM(CASE WHEN pb.is_paid = 0 AND (pb.payment_status != 'Cancelled' OR pb.payment_status IS NULL) THEN 1 ELSE 0 END) as unpaid_not_cancelled,
            SUM(CASE WHEN pb.is_paid = 0 
                AND (pb.payment_status != 'Cancelled' OR pb.payment_status IS NULL)
                AND (pb.payment_status != 'Paid' OR pb.payment_status IS NULL)
                AND COALESCE(pb.due_date, pb.period_start_date) IS NOT NULL
                THEN 1 ELSE 0 END) as unpaid_with_due_date,
            SUM(CASE WHEN pb.is_paid = 0 
                AND (pb.payment_status != 'Cancelled' OR pb.payment_status IS NULL)
                AND (pb.payment_status != 'Paid' OR pb.payment_status IS NULL)
                AND COALESCE(pb.due_date, pb.period_start_date) >= CURDATE()
                THEN 1 ELSE 0 END) as unpaid_future_due,
            SUM(CASE WHEN pb.is_paid = 0 
                AND (pb.payment_status != 'Cancelled' OR pb.payment_status IS NULL)
                AND (pb.payment_status != 'Paid' OR pb.payment_status IS NULL)
                AND COALESCE(pb.due_date, pb.period_start_date) >= CURDATE()
                AND COALESCE(pb.due_date, pb.period_start_date) <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)
                THEN 1 ELSE 0 END) as unpaid_within_5_days
        FROM payment_breakdowns pb
    ";
    $checkStmt = $pdo->query($checkSql);
    $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    logMessage("Database Analysis:");
    logMessage("  - Total breakdowns: " . $checkResult['total']);
    logMessage("  - Unpaid breakdowns: " . $checkResult['unpaid']);
    logMessage("  - Unpaid (not cancelled): " . $checkResult['unpaid_not_cancelled']);
    logMessage("  - Unpaid with due date: " . $checkResult['unpaid_with_due_date']);
    logMessage("  - Unpaid with future due date: " . $checkResult['unpaid_future_due']);
    logMessage("  - Unpaid within 5 days: " . $checkResult['unpaid_within_5_days']);
    
    // Get all unpaid payment breakdowns with due dates within the next 5 days or today
    // Reminders will be sent EVERY DAY from 5 days before until due date (5, 4, 3, 2, 1, 0)
    // Only get breakdowns that are not paid and not cancelled
    // Also get boarding house and room information for the notification message
    $sql = "
        SELECT 
            pb.breakdown_id,
            pb.booking_id,
            pb.due_date,
            pb.period_start_date,
            pb.amount,
            pb.period_label,
            pb.payment_status,
            pb.is_paid,
            b.user_id,
            COALESCE(pb.due_date, pb.period_start_date) as effective_due_date,
            DATEDIFF(COALESCE(pb.due_date, pb.period_start_date), CURDATE()) as days_diff,
            bh.bh_name as boarding_house_name,
            ru.room_number,
            bhr.room_name as room_category
        FROM payment_breakdowns pb
        INNER JOIN bookings b ON pb.booking_id = b.booking_id
        LEFT JOIN room_units ru ON b.room_id = ru.room_id
        LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        WHERE pb.is_paid = 0
            AND (pb.payment_status != 'Cancelled' OR pb.payment_status IS NULL)
            AND (pb.payment_status != 'Paid' OR pb.payment_status IS NULL)
            AND COALESCE(pb.due_date, pb.period_start_date) IS NOT NULL
            AND COALESCE(pb.due_date, pb.period_start_date) >= CURDATE()
            AND COALESCE(pb.due_date, pb.period_start_date) <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)
        ORDER BY pb.breakdown_id, COALESCE(pb.due_date, pb.period_start_date)
    ";
    
    $stmt = $pdo->query($sql);
    $breakdowns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($breakdowns) . " payment breakdown(s) to check");
    
    // Debug: Log breakdown details
    if (count($breakdowns) > 0) {
        logMessage("Breakdown details (found " . count($breakdowns) . " breakdown(s) matching criteria):");
        foreach ($breakdowns as $bd) {
            $dueDateSource = $bd['due_date'] ? 'due_date' : 'period_start_date';
            $daysDiff = isset($bd['days_diff']) ? $bd['days_diff'] : 'N/A';
            $bhName = $bd['boarding_house_name'] ?? 'N/A';
            $roomInfo = '';
            if (!empty($bd['room_number']) && !empty($bd['room_category'])) {
                $roomInfo = "Room {$bd['room_number']} ({$bd['room_category']})";
            } elseif (!empty($bd['room_number'])) {
                $roomInfo = "Room {$bd['room_number']}";
            } elseif (!empty($bd['room_category'])) {
                $roomInfo = $bd['room_category'];
            } else {
                $roomInfo = 'N/A';
            }
            logMessage("  - Breakdown ID: {$bd['breakdown_id']}, Due Date: {$bd['effective_due_date']} (from: $dueDateSource, days: $daysDiff), Amount: ₱" . number_format($bd['amount'], 2) . ", BH: $bhName, Room: $roomInfo, User ID: {$bd['user_id']}, Status: {$bd['payment_status']}, Is Paid: {$bd['is_paid']}");
        }
    } else {
        logMessage("No breakdowns found matching criteria.");
        logMessage("Checking database for unpaid breakdowns...");
        
        // Debug query to see what's in the database
        $debugSql = "
            SELECT 
                COUNT(*) as total_count,
                COUNT(CASE WHEN COALESCE(pb.due_date, pb.period_start_date) IS NOT NULL THEN 1 END) as with_due_date,
                COUNT(CASE WHEN COALESCE(pb.due_date, pb.period_start_date) >= CURDATE() THEN 1 END) as future_due_dates,
                COUNT(CASE WHEN COALESCE(pb.due_date, pb.period_start_date) <= DATE_ADD(CURDATE(), INTERVAL 5 DAY) THEN 1 END) as within_5_days
            FROM payment_breakdowns pb
            WHERE pb.is_paid = 0
                AND (pb.payment_status != 'Cancelled' OR pb.payment_status IS NULL)
                AND (pb.payment_status != 'Paid' OR pb.payment_status IS NULL)
        ";
        $debugStmt = $pdo->query($debugSql);
        $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
        
        logMessage("Database Statistics:");
        logMessage("  - Total unpaid breakdowns: " . $debugResult['total_count']);
        logMessage("  - Breakdowns with due dates: " . $debugResult['with_due_date']);
        logMessage("  - Breakdowns with future due dates: " . $debugResult['future_due_dates']);
        logMessage("  - Breakdowns within 5 days: " . $debugResult['within_5_days']);
        
        // Show some example breakdowns
        $exampleSql = "
            SELECT 
                pb.breakdown_id,
                pb.booking_id,
                COALESCE(pb.due_date, pb.period_start_date) as effective_due_date,
                pb.amount,
                pb.is_paid,
                pb.payment_status,
                DATEDIFF(COALESCE(pb.due_date, pb.period_start_date), CURDATE()) as days_until_due
            FROM payment_breakdowns pb
            WHERE pb.is_paid = 0
                AND (pb.payment_status != 'Cancelled' OR pb.payment_status IS NULL)
                AND (pb.payment_status != 'Paid' OR pb.payment_status IS NULL)
            ORDER BY COALESCE(pb.due_date, pb.period_start_date) ASC
            LIMIT 5
        ";
        $exampleStmt = $pdo->query($exampleSql);
        $examples = $exampleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($examples) > 0) {
            logMessage("Example unpaid breakdowns (first 5):");
            foreach ($examples as $ex) {
                $daysText = $ex['days_until_due'] > 0 ? "in {$ex['days_until_due']} days" : ($ex['days_until_due'] == 0 ? "today" : abs($ex['days_until_due']) . " days ago");
                logMessage("  - ID: {$ex['breakdown_id']}, Due: {$ex['effective_due_date']} ($daysText), Amount: ₱" . number_format($ex['amount'], 2) . ", Status: {$ex['payment_status']}");
            }
        }
    }
    
    foreach ($breakdowns as $breakdown) {
        $breakdownId = $breakdown['breakdown_id'];
        $userId = $breakdown['user_id'];
        $dueDate = $breakdown['effective_due_date'];
        $amount = $breakdown['amount'];
        $periodLabel = $breakdown['period_label'];
        $boardingHouseName = $breakdown['boarding_house_name'] ?? 'Boarding House';
        $roomNumber = $breakdown['room_number'] ?? '';
        $roomCategory = $breakdown['room_category'] ?? '';
        
        // Format room information
        $roomInfo = '';
        if (!empty($roomNumber) && !empty($roomCategory)) {
            $roomInfo = "Room $roomNumber ($roomCategory)";
        } elseif (!empty($roomNumber)) {
            $roomInfo = "Room $roomNumber";
        } elseif (!empty($roomCategory)) {
            $roomInfo = $roomCategory;
        }
        
        // Format boarding house and room text
        $locationText = '';
        if (!empty($boardingHouseName) && !empty($roomInfo)) {
            $locationText = " for $boardingHouseName - $roomInfo";
        } elseif (!empty($boardingHouseName)) {
            $locationText = " for $boardingHouseName";
        } elseif (!empty($roomInfo)) {
            $locationText = " for $roomInfo";
        }
        
        // Calculate days until due date
        $dueDateObj = new DateTime($dueDate);
        $todayObj = new DateTime($today);
        $todayObj->setTime(0, 0, 0); // Set to start of day
        $dueDateObj->setTime(0, 0, 0); // Set to start of day
        
        // Calculate difference in days
        // If due date is in the future, daysUntilDue will be positive
        // If due date is today, daysUntilDue will be 0
        // If due date is in the past, daysUntilDue will be negative
        $daysUntilDue = $todayObj->diff($dueDateObj)->days;
        
        // Adjust sign based on which date is later
        if ($dueDateObj < $todayObj) {
            $daysUntilDue = -$daysUntilDue; // Past due date
        } elseif ($dueDateObj > $todayObj) {
            // Future due date - keep positive
        } else {
            $daysUntilDue = 0; // Today is due date
        }
        
        logMessage("Processing Breakdown ID $breakdownId: Due Date = $dueDate, Days Until Due = $daysUntilDue");
        
        // Reminders are sent EVERY DAY from 5 days before until due date (5, 4, 3, 2, 1, 0)
        // So if daysUntilDue is between 0 and 5 (inclusive), send a reminder
        $reminderType = null;
        
        if ($daysUntilDue >= 0 && $daysUntilDue <= 5) {
            // Map days to reminder type for tracking
            if ($daysUntilDue == 5) {
                $reminderType = '5_days_before';
            } elseif ($daysUntilDue == 4) {
                $reminderType = '4_days_before';
            } elseif ($daysUntilDue == 3) {
                $reminderType = '3_days_before';
            } elseif ($daysUntilDue == 2) {
                $reminderType = '2_days_before';
            } elseif ($daysUntilDue == 1) {
                $reminderType = '1_day_before';
            } elseif ($daysUntilDue == 0) {
                $reminderType = 'due_date';
            }
        }
        
        // Skip if no reminder needed today (outside 0-5 days range)
        if (!$reminderType) {
            logMessage("Skipping Breakdown ID $breakdownId: Days until due ($daysUntilDue) is outside reminder range (0-5 days)");
            continue;
        }
        
        // Check if reminder was already sent today
        $checkSql = "
            SELECT log_id 
            FROM payment_reminder_logs 
            WHERE breakdown_id = ? 
                AND reminder_type = ? 
                AND reminder_date = ?
            LIMIT 1
        ";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$breakdownId, $reminderType, $today]);
        $existingReminder = $checkStmt->fetch();
        
        if ($existingReminder) {
            error_log("auto_notify_payment_reminders.php - Reminder already sent for breakdown_id $breakdownId, type: $reminderType, date: $today");
            continue;
        }
        
        // Get user information to verify user exists
        $userSql = "SELECT user_id FROM users WHERE user_id = ?";
        $userStmt = $pdo->prepare($userSql);
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            error_log("auto_notify_payment_reminders.php - User not found: user_id $userId for breakdown_id $breakdownId");
            $remindersFailed++;
            continue;
        }
        
        // Prepare reminder message based on days until due
        $daysText = '';
        if ($daysUntilDue == 5) {
            $daysText = '5 days';
        } elseif ($daysUntilDue == 4) {
            $daysText = '4 days';
        } elseif ($daysUntilDue == 3) {
            $daysText = '3 days';
        } elseif ($daysUntilDue == 2) {
            $daysText = '2 days';
        } elseif ($daysUntilDue == 1) {
            $daysText = '1 day';
        } else {
            $daysText = 'today';
        }
        
        $title = "Payment Reminder";
        $message = "Reminder: Your payment of ₱" . number_format($amount, 2) . " for " . $periodLabel . $locationText;
        if ($daysUntilDue > 0) {
            $message .= " is due in $daysText (Due: " . date('M d, Y', strtotime($dueDate)) . ").";
        } else {
            $message .= " is due today (" . date('M d, Y', strtotime($dueDate)) . "). Please make your payment to avoid late fees.";
        }
        
        // Send notification using NotificationHelper
        try {
            $notificationResult = NotificationHelper::createNotification(
                $userId,
                $title,
                $message,
                'payment',
                true // Send FCM push notification
            );
            
            $notifId = null;
            $fcmSent = false;
            
            if ($notificationResult && $notificationResult['success']) {
                $notifId = $notificationResult['data']['notif_id'] ?? null;
                $fcmSent = $notificationResult['data']['fcm_sent'] ?? false;
                
                // Log the reminder
                $logSql = "
                    INSERT INTO payment_reminder_logs 
                    (breakdown_id, user_id, reminder_type, due_date, reminder_date, notif_id, fcm_sent)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    $breakdownId,
                    $userId,
                    $reminderType,
                    $dueDate,
                    $today,
                    $notifId,
                    $fcmSent ? 1 : 0
                ]);
                
                $remindersSent++;
                logMessage("✓ Reminder sent successfully: Breakdown ID $breakdownId, User ID $userId, Type: $reminderType, Days: $daysUntilDue");
            } else {
                $errorMsg = $notificationResult['message'] ?? 'Unknown error';
                logMessage("✗ Failed to send reminder: Breakdown ID $breakdownId, User ID $userId, Error: $errorMsg");
                $remindersFailed++;
            }
        } catch (Exception $e) {
            logMessage("✗ Exception sending reminder: Breakdown ID $breakdownId, User ID $userId, Error: " . $e->getMessage());
            $remindersFailed++;
        }
    }
    
    // Log summary
    logMessage("=== Payment Reminder Check Completed ===");
    logMessage("Summary: Sent $remindersSent reminder(s), Failed: $remindersFailed");
    logMessage("=========================================");
    
    // Return JSON response if called via HTTP
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode([
            'success' => true,
            'message' => $summary,
            'reminders_sent' => $remindersSent,
            'reminders_failed' => $remindersFailed,
            'date' => $today
        ]);
    }
    
} catch (PDOException $e) {
    $errorMessage = "Database error in auto_notify_payment_reminders.php: " . $e->getMessage();
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
    }
} catch (Exception $e) {
    error_log("Error in auto_notify_payment_reminders.php: " . $e->getMessage());
    
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

