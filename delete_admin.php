<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$admin_id = $_POST['admin_id'] ?? '';

// Validation
if (empty($admin_id) || !is_numeric($admin_id)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid admin ID'));
    exit;
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT admin_id, name FROM admin_accounts WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode(array('success' => false, 'message' => 'Admin not found'));
        exit;
    }
    
    // Check if this is the last admin (prevent deleting all admins)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_accounts WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] <= 1) {
        echo json_encode(array('success' => false, 'message' => 'Cannot delete the last admin account'));
        exit;
    }
    
    // Delete admin
    $stmt = $conn->prepare("DELETE FROM admin_accounts WHERE admin_id = ?");
    $result = $stmt->execute([$admin_id]);
    
    if ($result) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Admin account deleted successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to delete admin account'));
    }
    
} catch(PDOException $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>


