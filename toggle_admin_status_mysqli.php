<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$admin_id = $_POST['admin_id'] ?? '';
$status = $_POST['status'] ?? '';

// Validation
if (empty($admin_id) || !is_numeric($admin_id)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid admin ID'));
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    echo json_encode(array('success' => false, 'message' => 'Invalid status'));
    exit;
}

try {
    // Check if admin exists
    $stmt = $conn->prepare("SELECT admin_id, name FROM admin_accounts WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!$admin) {
        echo json_encode(array('success' => false, 'message' => 'Admin not found'));
        exit;
    }
    
    // Check if this is the last active admin (prevent deactivating all admins)
    if ($status === 'inactive') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_accounts WHERE status = 'active' AND admin_id != ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc();
        
        if ($count['count'] == 0) {
            echo json_encode(array('success' => false, 'message' => 'Cannot deactivate the last active admin account'));
            exit;
        }
    }
    
    // Update admin status
    $stmt = $conn->prepare("UPDATE admin_accounts SET status = ? WHERE admin_id = ?");
    $stmt->bind_param("si", $status, $admin_id);
    
    if ($stmt->execute()) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Admin status updated successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update admin status'));
    }
    
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>


