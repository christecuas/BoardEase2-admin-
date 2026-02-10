<?php
// Prevent any HTML error reporting from breaking JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

function sendResponse($success, $message, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

try {
    require_once 'dbConfig.php';
    
    // Check if $conn exists and is connected
    if (!isset($conn) || $conn->connect_error) {
        sendResponse(false, 'Database connection failed: ' . ($conn->connect_error ?? 'Connection object not found'));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        sendResponse(false, 'Email and password are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Invalid email format');
    }

    // Check if admin exists
    $stmt = $conn->prepare("SELECT admin_id, name, email, password, role, status FROM admin_accounts WHERE email = ?");
    if (!$stmt) {
        sendResponse(false, 'Database table error: admin_accounts table may be missing or inaccessible.');
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!$admin) {
        sendResponse(false, 'Invalid email or password');
    }
    
    // Check if admin is active
    if ($admin['status'] !== 'active') {
        sendResponse(false, 'Your account is deactivated. Please contact administrator.');
    }
    
    // Verify password
    if (!password_verify($password, $admin['password'])) {
        sendResponse(false, 'Invalid email or password');
    }
    
    // Update last login
    $updateStmt = $conn->prepare("UPDATE admin_accounts SET last_login = NOW() WHERE admin_id = ?");
    if ($updateStmt) {
        $updateStmt->bind_param("i", $admin['admin_id']);
        $updateStmt->execute();
    }
    
    // Log admin login activity
    try {
        if (file_exists('log_admin_activity.php')) {
            require_once 'log_admin_activity.php';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            if (function_exists('logAdminActivity')) {
                logAdminActivity(
                    $admin['admin_id'],
                    'login',
                    $admin['name'] . ' logged in',
                    'Admin login successful from ' . ($ip_address ?? 'unknown IP'),
                    $ip_address,
                    $user_agent
                );
            }
        }
    } catch (Exception $e) {
        error_log("Warning: Failed to log admin login activity: " . $e->getMessage());
    }
    
    // Set session variables
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    sendResponse(true, 'Login successful! Welcome back, ' . $admin['name'] . '!');
    
} catch (Throwable $e) {
    sendResponse(false, 'System error: ' . $e->getMessage());
}
?>
