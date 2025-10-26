<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$admin_id = $_POST['admin_id'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'super_admin';

// Validation
if (empty($admin_id) || !is_numeric($admin_id)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid admin ID'));
    exit;
}

if (empty($name) || empty($email)) {
    echo json_encode(array('success' => false, 'message' => 'Name and email are required'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid email format'));
    exit;
}

try {
    // Check if admin exists
    $stmt = $conn->prepare("SELECT admin_id FROM admin_accounts WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Admin not found'));
        exit;
    }
    
    // Check if email already exists for other admins
    $stmt = $conn->prepare("SELECT admin_id FROM admin_accounts WHERE email = ? AND admin_id != ?");
    $stmt->bind_param("si", $email, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(array('success' => false, 'message' => 'Email already exists'));
        exit;
    }
    
    // Prepare update query
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(array('success' => false, 'message' => 'Password must be at least 6 characters'));
            exit;
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_accounts SET name = ?, email = ?, password = ?, role = ? WHERE admin_id = ?");
        $stmt->bind_param("ssssi", $name, $email, $hashedPassword, $role, $admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE admin_accounts SET name = ?, email = ?, role = ? WHERE admin_id = ?");
        $stmt->bind_param("sssi", $name, $email, $role, $admin_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Admin account updated successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update admin account'));
    }
    
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>


