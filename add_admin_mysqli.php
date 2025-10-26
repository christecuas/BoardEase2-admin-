<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'super_admin';

// Validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(array('success' => false, 'message' => 'All fields are required'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid email format'));
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(array('success' => false, 'message' => 'Password must be at least 6 characters'));
    exit;
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT admin_id FROM admin_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(array('success' => false, 'message' => 'Email already exists'));
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new admin
    $stmt = $conn->prepare("INSERT INTO admin_accounts (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
    
    if ($stmt->execute()) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Admin account created successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to create admin account'));
    }
    
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>


