<?php
// Test database connection
header("Content-Type: application/json");

try {
    require_once 'dbConfig.php';
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as user_count FROM users");
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful!',
        'user_count' => $row['user_count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>










