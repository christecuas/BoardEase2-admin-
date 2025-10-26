<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT admin_id, name, email, role, status, last_login, created_at FROM admin_accounts ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for frontend
    $formattedAdmins = array();
    foreach ($admins as $admin) {
        $formattedAdmins[] = array(
            'id' => $admin['admin_id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role' => $admin['role'],
            'status' => $admin['status'],
            'last_login' => $admin['last_login'],
            'created_at' => $admin['created_at']
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'admins' => $formattedAdmins
    ));
    
} catch(PDOException $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>


