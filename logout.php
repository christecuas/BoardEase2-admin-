<?php
// logout.php
// Logs the user out by setting is_logged_in TO 0

header('Content-Type: application/json');
require_once 'dbConfig.php';

try {
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $user_id = $_REQUEST['user_id'] ?? null;
    
    if ($user_id) {
        // Set is_active = 0 for all device tokens of this user
        // This marks them as "offline" effectively
        $stmt = $conn->prepare("UPDATE device_tokens SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
