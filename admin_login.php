<?php
session_start();
header('Content-Type: application/json');
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
if (empty($email) || empty($password)) {
    echo json_encode(array('success' => false, 'message' => 'Email and password are required'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid email format'));
    exit;
}

try {
    // Check if admin exists
    $stmt = $conn->prepare("SELECT admin_id, name, email, password, role, status FROM admin_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!$admin) {
        echo json_encode(array('success' => false, 'message' => 'Invalid email or password'));
        exit;
    }
    
    // Check if admin is active
    if ($admin['status'] !== 'active') {
        echo json_encode(array('success' => false, 'message' => 'Your account is deactivated. Please contact administrator.'));
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $admin['password'])) {
        echo json_encode(array('success' => false, 'message' => 'Invalid email or password'));
        exit;
    }
    
    // Update last login
    $updateStmt = $conn->prepare("UPDATE admin_accounts SET last_login = NOW() WHERE admin_id = ?");
    $updateStmt->bind_param("i", $admin['admin_id']);
    $updateStmt->execute();
    
    // Set session variables
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    echo json_encode(array(
        'success' => true,
        'message' => 'Login successful! Welcome back, ' . $admin['name'] . '!'
    ));
    
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>


