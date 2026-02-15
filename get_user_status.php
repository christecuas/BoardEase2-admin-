<?php
// get_user_status.php
// Returns the current status and role of a user
// Used for real-time dashboard updates

require_once 'dbConfig.php';

header('Content-Type: application/json');

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]);
        exit();
    }

    $stmt = $conn->prepare("SELECT status, role FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'status' => $row['status'],
            'role' => $row['role']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => "User not found."]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => "User ID required."]);
}
?>
