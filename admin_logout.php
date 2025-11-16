<?php
session_start();

// Log admin logout activity (before destroying session)
if (isset($_SESSION['admin_id'])) {
    try {
        require_once 'log_admin_activity.php';
        require_once 'dbConfig.php';
        
        $admin_id = $_SESSION['admin_id'];
        $admin_name = $_SESSION['admin_name'] ?? 'Admin';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        logAdminActivity(
            $admin_id,
            'logout',
            $admin_name . ' logged out',
            'Admin logout successful',
            $ip_address,
            $user_agent
        );
    } catch (Exception $e) {
        error_log("Warning: Failed to log admin logout activity: " . $e->getMessage());
    }
}

// Destroy all session data
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header('Location: html/admin_login.php');
exit;
?>















