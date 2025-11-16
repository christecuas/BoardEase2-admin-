<?php
session_start();
header('Content-Type: application/json');
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$target_admin_id = $_POST['admin_id'] ?? '';
$status = $_POST['status'] ?? '';

// Get current admin ID from session (who is performing the action)
$current_admin_id = $_SESSION['admin_id'] ?? null;

// Validation
if (empty($target_admin_id) || !is_numeric($target_admin_id)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid admin ID'));
    exit;
}

if (!in_array($status, ['active', 'inactive'])) {
    echo json_encode(array('success' => false, 'message' => 'Invalid status'));
    exit;
}

try {
    // Get current admin data before update
    $stmt = $conn->prepare("SELECT admin_id, name, email, status FROM admin_accounts WHERE admin_id = ?");
    $stmt->bind_param("i", $target_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!$admin) {
        echo json_encode(array('success' => false, 'message' => 'Admin not found'));
        exit;
    }
    
    $oldStatus = $admin['status'];
    $adminName = $admin['name'];
    $adminEmail = $admin['email'];
    
    // Check if status actually changed
    if ($oldStatus === $status) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Admin status is already ' . $status
        ));
        exit;
    }
    
    // Check if this is the last active admin (prevent deactivating all admins)
    if ($status === 'inactive') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_accounts WHERE status = 'active' AND admin_id != ?");
        $stmt->bind_param("i", $target_admin_id);
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
    $stmt->bind_param("si", $status, $target_admin_id);
    
    if ($stmt->execute()) {
        // Log activity after successful update
        if ($current_admin_id) {
            try {
                require_once 'log_admin_activity.php';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                
                $statusText = ucfirst($status);
                $actionText = $status === 'active' ? 'activated' : 'deactivated';
                
                $logResult = logAdminActivity(
                    $current_admin_id,
                    'status_change',
                    'Account Status: ' . $statusText . ' - ' . $adminName,
                    'Admin account ' . $actionText . ': Admin ID ' . $target_admin_id . ', Name: ' . $adminName . ', Email: ' . $adminEmail . ', Previous Status: ' . ucfirst($oldStatus),
                    $ip_address,
                    $user_agent
                );
                if ($logResult) {
                    error_log("Status change activity logged: activity_id=$logResult");
                } else {
                    error_log("Warning: Failed to log status change activity for admin_id=$current_admin_id, target_admin_id=$target_admin_id");
                }
            } catch (Exception $logError) {
                error_log("Error logging admin activity: " . $logError->getMessage());
                // Don't fail the update if logging fails
            }
        } else {
            error_log("Warning: current_admin_id is not set in session. Cannot log admin activity for status change. target_admin_id=$target_admin_id");
        }
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Admin status updated successfully',
            'status' => $status,
            'target_admin_id' => $target_admin_id
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update admin status: ' . $stmt->error));
    }
    
} catch(Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ));
}
?>















