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
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT admin_id FROM admin_accounts WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    if (!$stmt->fetch()) {
        echo json_encode(array('success' => false, 'message' => 'Admin not found'));
        exit;
    }
    
    // Check if email already exists for other admins
    $stmt = $conn->prepare("SELECT admin_id FROM admin_accounts WHERE email = ? AND admin_id != ?");
    $stmt->execute([$email, $admin_id]);
    if ($stmt->fetch()) {
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
        $result = $stmt->execute([$name, $email, $hashedPassword, $role, $admin_id]);
    } else {
        $stmt = $conn->prepare("UPDATE admin_accounts SET name = ?, email = ?, role = ? WHERE admin_id = ?");
        $result = $stmt->execute([$name, $email, $role, $admin_id]);
    }
    
    if ($result) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Admin account updated successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update admin account'));
    }
    
} catch(PDOException $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>


