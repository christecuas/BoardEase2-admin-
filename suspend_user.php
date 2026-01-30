<?php
// suspend_user.php - Handle user suspension

// Start session to get admin_id
session_start();

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Include email configuration and templates
require_once 'email_config.php';
require_once 'email_templates.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

try {
    // Database connection
    // Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get POST data
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'suspend' or 'unsuspend'
    $reason = $_POST['reason'] ?? '';

    if (!$userId || !$action) {
        throw new Exception("Missing required parameters");
    }

    if (!in_array($action, ['suspend', 'unsuspend'])) {
        throw new Exception("Invalid action. Must be 'suspend' or 'unsuspend'");
    }

    // Get admin_id from session
    $admin_id = $_SESSION['admin_id'] ?? null;
    if (!$admin_id) {
        throw new Exception("Admin session not found");
    }

    // Start transaction
    $conn->autocommit(FALSE);
    $conn->begin_transaction();

    // Get user information before suspension
    $getUserSql = "SELECT u.user_id, u.status, r.email, r.first_name, r.last_name 
                   FROM users u 
                   JOIN registrations r ON u.reg_id = r.id 
                   WHERE u.user_id = ?";
    $getUserStmt = $conn->prepare($getUserSql);
    $getUserStmt->bind_param("i", $userId);
    $getUserStmt->execute();
    $userResult = $getUserStmt->get_result();
    
    if ($userResult->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $userData = $userResult->fetch_assoc();
    $getUserStmt->close();

    // Determine new status
    $newStatus = ($action === 'suspend') ? 'Inactive' : 'Active';

    // Update user status
    $updateSql = "UPDATE users SET status = ? WHERE user_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $userId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update user status: " . $conn->error);
    }
    $updateStmt->close();

    // Log admin activity
    if ($admin_id) {
        try {
            require_once 'log_admin_activity.php';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $actionText = ($action === 'suspend') ? 'user_suspended' : 'user_unsuspended';
            $description = ($action === 'suspend') 
                ? "User account suspended: {$userData['first_name']} {$userData['last_name']}" 
                : "User account unsuspended: {$userData['first_name']} {$userData['last_name']}";
            $details = "User ID: $userId, Email: {$userData['email']}" . 
                      ($reason ? ", Reason: $reason" : "");
            
            logAdminActivity(
                $admin_id,
                $actionText,
                $description,
                $details,
                $ip_address,
                $user_agent
            );
        } catch (Exception $e) {
            error_log("Warning: Failed to log admin activity: " . $e->getMessage());
        }
    }

    // Commit transaction
    $conn->commit();
    $conn->autocommit(TRUE);

    // Send email notification AFTER commit (if suspension/unsuspension)
    if (isset($userData['email']) && isset($userData['first_name']) && isset($userData['last_name'])) {
        $userName = trim($userData['first_name'] . ' ' . $userData['last_name']);
        $userEmail = $userData['email'];
        
        try {
            if ($action === 'suspend') {
                $emailSubject = "Account Suspended - BoardEase";
                $emailMessage = getAccountSuspensionEmailTemplate($userName, $userEmail, $reason);
            } else {
                $emailSubject = "Account Reactivated - BoardEase";
                $emailMessage = getAccountUnsuspensionEmailTemplate($userName, $userEmail);
            }
            
            $emailSent = sendEmail($userEmail, $emailSubject, $emailMessage);
            
            if ($emailSent) {
                error_log(($action === 'suspend' ? "Suspension" : "Unsuspension") . " email sent successfully to: " . $userEmail);
            } else {
                error_log("Failed to send " . ($action === 'suspend' ? "suspension" : "unsuspension") . " email to: " . $userEmail);
            }
        } catch (Exception $e) {
            error_log("Warning: Failed to send " . ($action === 'suspend' ? "suspension" : "unsuspension") . " email: " . $e->getMessage());
        }
    }

    // Send response
    echo json_encode([
        'success' => true,
        'message' => ($action === 'suspend') ? 'User suspended successfully' : 'User unsuspended successfully',
        'user_id' => $userId,
        'new_status' => $newStatus
    ]);

    $conn->close();

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->autocommit(FALSE)) {
        $conn->rollback();
        $conn->autocommit(TRUE);
    }
    
    error_log("Error in suspend_user.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

