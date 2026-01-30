<?php
// deactivate_boarding_house.php - Handle boarding house deactivation

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
    $houseId = $_POST['bh_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'deactivate' or 'activate'
    $reason = $_POST['reason'] ?? '';

    if (!$houseId || !$action) {
        throw new Exception("Missing required parameters");
    }

    if (!in_array($action, ['deactivate', 'activate'])) {
        throw new Exception("Invalid action. Must be 'deactivate' or 'activate'");
    }

    // Get admin_id from session
    $admin_id = $_SESSION['admin_id'] ?? null;
    if (!$admin_id) {
        throw new Exception("Admin session not found");
    }

    // Start transaction
    $conn->autocommit(FALSE);
    $conn->begin_transaction();

    // Get boarding house information before deactivation
    $getHouseSql = "SELECT bh.bh_id, bh.bh_name, bh.status, bh.user_id,
                           CONCAT(r.first_name, ' ', r.last_name) as owner_name, r.email
                    FROM boarding_houses bh
                    JOIN users u ON bh.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE bh.bh_id = ?";
    $getHouseStmt = $conn->prepare($getHouseSql);
    $getHouseStmt->bind_param("i", $houseId);
    $getHouseStmt->execute();
    $houseResult = $getHouseStmt->get_result();
    
    if ($houseResult->num_rows === 0) {
        throw new Exception("Boarding house not found");
    }
    
    $houseData = $houseResult->fetch_assoc();
    $getHouseStmt->close();

    // Determine new status
    $newStatus = ($action === 'deactivate') ? 'Inactive' : 'Active';

    // Update boarding house status
    $updateSql = "UPDATE boarding_houses SET status = ? WHERE bh_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $houseId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update boarding house status: " . $conn->error);
    }
    $updateStmt->close();

    // Log admin activity
    if ($admin_id) {
        try {
            require_once 'log_admin_activity.php';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $actionText = ($action === 'deactivate') ? 'boarding_house_deactivated' : 'boarding_house_activated';
            $description = ($action === 'deactivate') 
                ? "Boarding house deactivated: {$houseData['bh_name']}" 
                : "Boarding house activated: {$houseData['bh_name']}";
            $details = "Boarding House ID: $houseId, Name: {$houseData['bh_name']}, " .
                      "Owner: {$houseData['owner_name']} ({$houseData['email']})" . 
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

    // Send email notification AFTER commit (if deactivation/activation)
    if (isset($houseData['email']) && isset($houseData['owner_name']) && isset($houseData['bh_name'])) {
        $ownerName = $houseData['owner_name'];
        $ownerEmail = $houseData['email'];
        $bhName = $houseData['bh_name'];
        
        try {
            if ($action === 'deactivate') {
                $emailSubject = "Boarding House Deactivated - BoardEase";
                $emailMessage = getBoardingHouseDeactivationEmailTemplate($ownerName, $ownerEmail, $bhName, $reason);
            } else {
                $emailSubject = "Boarding House Reactivated - BoardEase";
                $emailMessage = getBoardingHouseActivationEmailTemplate($ownerName, $ownerEmail, $bhName);
            }
            
            $emailSent = sendEmail($ownerEmail, $emailSubject, $emailMessage);
            
            if ($emailSent) {
                error_log(($action === 'deactivate' ? "Deactivation" : "Activation") . " email sent successfully to: " . $ownerEmail);
            } else {
                error_log("Failed to send " . ($action === 'deactivate' ? "deactivation" : "activation") . " email to: " . $ownerEmail);
            }
        } catch (Exception $e) {
            error_log("Warning: Failed to send " . ($action === 'deactivate' ? "deactivation" : "activation") . " email: " . $e->getMessage());
        }
    }

    // Send response
    echo json_encode([
        'success' => true,
        'message' => ($action === 'deactivate') ? 'Boarding house deactivated successfully' : 'Boarding house activated successfully',
        'bh_id' => $houseId,
        'new_status' => $newStatus
    ]);

    $conn->close();

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->autocommit(FALSE)) {
        $conn->rollback();
        $conn->autocommit(TRUE);
    }
    
    error_log("Error in deactivate_boarding_house.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

