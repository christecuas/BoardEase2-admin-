<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

try {
    // Use the existing MySQLi connection from dbConfig.php
    $result = $conn->query("SELECT admin_id, name, email, role, status, last_login, created_at FROM admin_accounts ORDER BY created_at DESC");
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $admins = array();
    while ($row = $result->fetch_assoc()) {
        $admins[] = array(
            'id' => $row['admin_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'status' => $row['status'],
            'last_login' => $row['last_login'],
            'created_at' => $row['created_at']
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'admins' => $admins
    ));
    
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>



