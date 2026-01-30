<?php
// update_activity_heartbeat.php
// Updates the user's last_activity timestamp and is_logged_in status
// Should be called periodically (e.g., every 30s-1min) by the Android app when in foreground

header('Content-Type: application/json');
require_once 'dbConfig.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get user_id from POST or GET
    $user_id = $_REQUEST['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception("User ID is required");
    }
    
    // Update the user's activity
    // Set is_logged_in = 1 since they are sending a heartbeat
    // Update the device_token activity
    // Set updated_at = NOW() for all active tokens belonging to this user
    $stmt = $conn->prepare("UPDATE device_tokens SET updated_at = NOW(), is_active = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity updated']);
    } else {
        throw new Exception("Error updating activity: " . $conn->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
