<?php
/**
 * Cleanup old notifications to save database storage
 * This script automatically deletes notifications older than 1 month (30 days)
 * 
 * Usage: php cleanup_old_notifications.php
 * Or set up cron: 0 2 * * * php /path/to/cleanup_old_notifications.php
 * (Runs daily at 2 AM)
 * 
 * Configuration:
 * - KEEP_DAYS: Number of days to keep notifications (default: 30 days = 1 month)
 * - DELETE_READ_ONLY: If true, only delete READ notifications older than 1 month
 *                     Unread notifications are kept FOREVER
 *                     If false, delete ALL notifications (read and unread) older than 1 month
 * 
 * Default behavior:
 * - Deletes READ notifications older than 1 month (30 days)
 * - Keeps UNREAD notifications forever (regardless of age)
 */

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

// Configuration
define('KEEP_DAYS', 30); // Keep notifications for 30 days (1 month)
define('DELETE_READ_ONLY', true); // Only delete read notifications older than 1 month (keep unread forever)

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Set log file path
$logFile = $logDir . '/notification_cleanup.log';
$dailyLogFile = $logDir . '/notification_cleanup_' . date('Y-m-d') . '.log';

// Function to log messages
function logCleanup($message, $logFile, $dailyLogFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    file_put_contents($dailyLogFile, $logMessage, FILE_APPEND);
    error_log($message); // Also log to PHP error log
}

// Log start
logCleanup("=== Notification Cleanup Started ===", $logFile, $dailyLogFile);
logCleanup("Configuration: KEEP_DAYS=" . KEEP_DAYS . " (1 month), DELETE_READ_ONLY=" . (DELETE_READ_ONLY ? 'true' : 'false'), $logFile, $dailyLogFile);

try {
    logCleanup("Connecting to database...", $logFile, $dailyLogFile);
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logCleanup("Database connection successful", $logFile, $dailyLogFile);
    
    // Start transaction
    $pdo->beginTransaction();
    logCleanup("Transaction started", $logFile, $dailyLogFile);
    
    // Calculate cutoff date (1 month ago)
    $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . KEEP_DAYS . ' days'));
    logCleanup("Cutoff date calculated: $cutoffDate (notifications older than this will be deleted)", $logFile, $dailyLogFile);
    
    // First, count how many notifications will be deleted
    if (DELETE_READ_ONLY) {
        $countSql = "
            SELECT COUNT(*) as count
            FROM notifications
            WHERE notif_status = 'read'
                AND notif_created_at < ?
        ";
        $message = "Deleting read notifications older than 1 month (30 days)";
    } else {
        $countSql = "
            SELECT COUNT(*) as count
            FROM notifications
            WHERE notif_created_at < ?
        ";
        $message = "Deleting all notifications older than 1 month (30 days)";
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$cutoffDate]);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $notificationsToDelete = intval($countResult['count']);
    
    logCleanup("Found $notificationsToDelete notification(s) to delete", $logFile, $dailyLogFile);
    
    if ($notificationsToDelete > 0) {
        // Build delete query based on configuration
        if (DELETE_READ_ONLY) {
            // Only delete read notifications older than 1 month (30 days)
            // Keep unread notifications forever
            $sql = "
                DELETE FROM notifications
                WHERE notif_status = 'read'
                    AND notif_created_at < ?
            ";
        } else {
            // Delete all notifications (read and unread) older than 1 month
            $sql = "
                DELETE FROM notifications
                WHERE notif_created_at < ?
            ";
        }
        
        logCleanup("Executing delete query: $message", $logFile, $dailyLogFile);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cutoffDate]);
        $deletedCount = $stmt->rowCount();
        
        // Commit transaction
        $pdo->commit();
        logCleanup("Transaction committed successfully", $logFile, $dailyLogFile);
        
        // Log summary
        $summary = "Deleted: $deletedCount old notification(s) older than 1 month (cutoff: $cutoffDate)";
        logCleanup($summary, $logFile, $dailyLogFile);
        
        // If running from command line, output summary
        if (php_sapi_name() === 'cli') {
            echo "cleanup_old_notifications.php - $summary\n";
            echo "Configuration: KEEP_DAYS=" . KEEP_DAYS . " (1 month), DELETE_READ_ONLY=" . (DELETE_READ_ONLY ? 'true' : 'false') . "\n";
            echo "Note: Unread notifications are kept forever.\n";
        }
    } else {
        // No notifications to delete
        $pdo->commit();
        logCleanup("No notifications found to delete (all notifications are newer than cutoff date)", $logFile, $dailyLogFile);
        
        if (php_sapi_name() === 'cli') {
            echo "cleanup_old_notifications.php - No notifications to delete (all are newer than $cutoffDate)\n";
            echo "Configuration: KEEP_DAYS=" . KEEP_DAYS . " (1 month), DELETE_READ_ONLY=" . (DELETE_READ_ONLY ? 'true' : 'false') . "\n";
        }
        
        $deletedCount = 0;
    }
    
    logCleanup("=== Notification Cleanup Completed Successfully ===", $logFile, $dailyLogFile);
    
    // Return JSON response if called via HTTP
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode([
            'success' => true,
            'message' => $summary,
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate,
            'keep_days' => KEEP_DAYS,
            'delete_read_only' => DELETE_READ_ONLY
        ]);
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        logCleanup("Transaction rolled back due to error", $logFile, $dailyLogFile);
    }
    
    $errorMessage = "Database error in cleanup_old_notifications.php: " . $e->getMessage();
    
    // Check if it's a connection error
    if (strpos($e->getMessage(), 'refused') !== false || 
        strpos($e->getMessage(), 'Connection refused') !== false ||
        strpos($e->getMessage(), 'target machine actively refused') !== false) {
        $errorMessage .= " - MySQL/XAMPP may not be running. Please start MySQL service.";
    }
    
    logCleanup("ERROR: $errorMessage", $logFile, $dailyLogFile);
    logCleanup("=== Notification Cleanup Failed ===", $logFile, $dailyLogFile);
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
    } else {
        echo "ERROR: $errorMessage\n";
        if (strpos($e->getMessage(), 'refused') !== false) {
            echo "TIP: Make sure MySQL/XAMPP is running before running this script.\n";
        }
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        logCleanup("Transaction rolled back due to error", $logFile, $dailyLogFile);
    }
    
    $errorMessage = "Error in cleanup_old_notifications.php: " . $e->getMessage();
    logCleanup("ERROR: $errorMessage", $logFile, $dailyLogFile);
    logCleanup("=== Notification Cleanup Failed ===", $logFile, $dailyLogFile);
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
    } else {
        echo "ERROR: $errorMessage\n";
    }
}
?>

