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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Calculate cutoff date (1 month ago)
    $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . KEEP_DAYS . ' days'));
    
    // Build delete query based on configuration
    if (DELETE_READ_ONLY) {
        // Only delete read notifications older than 1 month (30 days)
        // Keep unread notifications forever
        $sql = "
            DELETE FROM notifications
            WHERE notif_status = 'read'
                AND notif_created_at < ?
        ";
        $message = "Deleting read notifications older than 1 month (30 days)";
    } else {
        // Delete all notifications (read and unread) older than 1 month
        $sql = "
            DELETE FROM notifications
            WHERE notif_created_at < ?
        ";
        $message = "Deleting all notifications older than 1 month (30 days)";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cutoffDate]);
    $deletedCount = $stmt->rowCount();
    
    // Commit transaction
    $pdo->commit();
    
    // Log summary
    $summary = "cleanup_old_notifications.php - Deleted: $deletedCount old notifications older than 1 month (cutoff: $cutoffDate)";
    error_log($summary);
    
    // If running from command line, output summary
    if (php_sapi_name() === 'cli') {
        echo $summary . "\n";
        echo "Configuration: KEEP_DAYS=" . KEEP_DAYS . " (1 month), DELETE_READ_ONLY=" . (DELETE_READ_ONLY ? 'true' : 'false') . "\n";
        echo "Note: Unread notifications are kept forever.\n";
    }
    
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
    }
    error_log("Database error in cleanup_old_notifications.php: " . $e->getMessage());
    
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
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in cleanup_old_notifications.php: " . $e->getMessage());
    
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

