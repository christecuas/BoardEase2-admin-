<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Not logged in',
        'redirect' => 'admin_login.php'
    ));
    exit;
}

// Check session timeout (24 hours)
$session_timeout = 24 * 60 * 60; // 24 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    // Session expired
    session_destroy();
    echo json_encode(array(
        'success' => false,
        'message' => 'Session expired',
        'redirect' => 'admin_login.php'
    ));
    exit;
}

// Return admin info
echo json_encode(array(
    'success' => true,
    'admin' => array(
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'],
        'email' => $_SESSION['admin_email'],
        'role' => $_SESSION['admin_role']
    )
));
?>



