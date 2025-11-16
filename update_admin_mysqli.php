<?php
session_start();
header('Content-Type: application/json');
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$target_admin_id = $_POST['admin_id'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'super_admin';

// Get current admin ID from session (who is performing the action)
$current_admin_id = $_SESSION['admin_id'] ?? null;

// Validation
if (empty($target_admin_id) || !is_numeric($target_admin_id)) {
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
    // Get current admin data before update
    $stmt = $conn->prepare("SELECT admin_id, name, email FROM admin_accounts WHERE admin_id = ?");
    $stmt->bind_param("i", $target_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Admin not found'));
        exit;
    }
    
    $oldAdmin = $result->fetch_assoc();
    $oldEmail = $oldAdmin['email'];
    $oldName = $oldAdmin['name'];
    $passwordChanged = false;
    $emailChanged = false;
    
    // Check if email already exists for other admins
    $stmt = $conn->prepare("SELECT admin_id FROM admin_accounts WHERE email = ? AND admin_id != ?");
    $stmt->bind_param("si", $email, $target_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(array('success' => false, 'message' => 'Email already exists'));
        exit;
    }
    
    // Check if email changed
    if ($oldEmail !== $email) {
        $emailChanged = true;
    }
    
    // Prepare update query
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(array('success' => false, 'message' => 'Password must be at least 6 characters'));
            exit;
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $passwordChanged = true;
        $stmt = $conn->prepare("UPDATE admin_accounts SET name = ?, email = ?, password = ?, role = ? WHERE admin_id = ?");
        $stmt->bind_param("ssssi", $name, $email, $hashedPassword, $role, $target_admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE admin_accounts SET name = ?, email = ?, role = ? WHERE admin_id = ?");
        $stmt->bind_param("sssi", $name, $email, $role, $target_admin_id);
    }
    
    if ($stmt->execute()) {
        $updateSuccess = true;
        $responseMessage = 'Admin account updated successfully';
        
        // Log activities after successful update
        if ($current_admin_id) {
            try {
                require_once 'log_admin_activity.php';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                
                // Log password change
                if ($passwordChanged) {
                    $logResult = logAdminActivity(
                        $current_admin_id,
                        'password_change',
                        'Password changed for admin: ' . $oldName,
                        'Admin ID: ' . $target_admin_id . ', Email: ' . $email,
                        $ip_address,
                        $user_agent
                    );
                    if ($logResult) {
                        error_log("Password change activity logged: activity_id=$logResult");
                    } else {
                        error_log("Warning: Failed to log password change activity for admin_id=$current_admin_id, target_admin_id=$target_admin_id");
                    }
                }
                
                // Log email change
                if ($emailChanged) {
                    $logResult = logAdminActivity(
                        $current_admin_id,
                        'email_change',
                        'Email changed for admin: ' . $oldName,
                        'Admin ID: ' . $target_admin_id . ', Old Email: ' . $oldEmail . ', New Email: ' . $email,
                        $ip_address,
                        $user_agent
                    );
                    if ($logResult) {
                        error_log("Email change activity logged: activity_id=$logResult");
                    } else {
                        error_log("Warning: Failed to log email change activity for admin_id=$current_admin_id, target_admin_id=$target_admin_id");
                    }
                }
            } catch (Exception $logError) {
                error_log("Error logging admin activity: " . $logError->getMessage());
                // Don't fail the update if logging fails
            }
        } else {
            error_log("Warning: current_admin_id is not set in session. Cannot log admin activity for admin update. target_admin_id=$target_admin_id");
        }
        
        echo json_encode(array(
            'success' => true,
            'message' => $responseMessage,
            'password_changed' => $passwordChanged,
            'email_changed' => $emailChanged
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update admin account: ' . $stmt->error));
    }
    
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>















