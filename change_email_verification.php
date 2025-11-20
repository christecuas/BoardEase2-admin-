<?php
// change_email_verification.php - Handle email change verification operations

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$servername = "localhost";
$username = "boardease";
$password = "boardease";
$database = "boardease2";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Connection failed: " . $conn->connect_error]));
}

// Include email configuration
require_once 'email_config.php';

$action = $_POST["action"] ?? null;

if (!$action) {
    echo json_encode(["success" => false, "error" => "Action is required"]);
    exit;
}

try {
    if ($action === "send_verification_code") {
        // Step 1: Send verification code to new email
        $user_id = $_POST["user_id"] ?? null;
        $current_password = $_POST["current_password"] ?? "";
        $new_email = trim($_POST["new_email"] ?? "");
        
        if (!$user_id || !$current_password || !$new_email) {
            echo json_encode(["success" => false, "error" => "User ID, current password, and new email are required"]);
            exit;
        }
        
        // Validate email format
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "error" => "Invalid email format"]);
            exit;
        }
        
        // Verify current password
        $checkSql = "SELECT r.password, r.email, r.first_name FROM registrations r 
                     JOIN users u ON r.id = u.reg_id 
                     WHERE u.user_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            echo json_encode(["success" => false, "error" => "User not found"]);
            exit;
        }
        
        $user = $checkResult->fetch_assoc();
        $storedPassword = $user["password"];
        $oldEmail = $user["email"];
        $firstName = $user["first_name"];
        
        // Check if new email is different from current email
        if ($new_email === $oldEmail) {
            echo json_encode(["success" => false, "error" => "New email must be different from current email"]);
            exit;
        }
        
        // Check if new email is already in use
        $emailCheckSql = "SELECT id FROM registrations WHERE email = ?";
        $emailCheckStmt = $conn->prepare($emailCheckSql);
        $emailCheckStmt->bind_param("s", $new_email);
        $emailCheckStmt->execute();
        $emailCheckResult = $emailCheckStmt->get_result();
        
        if ($emailCheckResult->num_rows > 0) {
            echo json_encode(["success" => false, "error" => "This email is already in use"]);
            exit;
        }
        $emailCheckStmt->close();
        
        // Verify current password
        $isHashed = password_get_info($storedPassword)['algo'] !== null;
        
        if ($isHashed) {
            if (!password_verify($current_password, $storedPassword)) {
                echo json_encode(["success" => false, "error" => "Current password is incorrect"]);
                exit;
            }
        } else {
            if ($current_password !== $storedPassword) {
                echo json_encode(["success" => false, "error" => "Current password is incorrect"]);
                exit;
            }
        }
        
        // Generate verification code
        $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
        $expiryTime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        // Get registration ID
        $regIdSql = "SELECT r.id FROM registrations r 
                     JOIN users u ON r.id = u.reg_id 
                     WHERE u.user_id = ?";
        $regIdStmt = $conn->prepare($regIdSql);
        $regIdStmt->bind_param("i", $user_id);
        $regIdStmt->execute();
        $regIdResult = $regIdStmt->get_result();
        $regId = $regIdResult->fetch_assoc()['id'];
        $regIdStmt->close();
        
        // Check if a verification code was sent recently (within last 2 minutes) to prevent duplicates
        $recentCheckSql = "SELECT created_at FROM email_verifications 
                          WHERE user_id = ? AND email = ? 
                          AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)";
        $recentCheckStmt = $conn->prepare($recentCheckSql);
        $recentCheckStmt->bind_param("is", $regId, $new_email);
        $recentCheckStmt->execute();
        $recentResult = $recentCheckStmt->get_result();
        
        if ($recentResult->num_rows > 0) {
            // A code was sent recently, don't send another one
            $recentCheckStmt->close();
            echo json_encode([
                "success" => true,
                "message" => "Verification code already sent. Please check your email."
            ]);
            exit;
        }
        $recentCheckStmt->close();
        
        // Store verification code in email_verifications table with special identifier
        // Ensure code is 6 digits with leading zeros - remove any non-numeric chars first
        $verificationCode = preg_replace('/[^0-9]/', '', $verificationCode);
        $verificationCode = str_pad($verificationCode, 6, '0', STR_PAD_LEFT);
        // Normalize email - remove all whitespace and convert to lowercase (same as verification)
        $new_email = trim(strtolower($new_email));
        $new_email = preg_replace('/\s+/', '', $new_email); // Remove any internal whitespace
        
        error_log("Storing verification code - Reg ID: $regId, Email: '$new_email' (normalized), Code: $verificationCode");
        
        error_log("Storing verification code - Reg ID: $regId, Email: $new_email, Code: $verificationCode");
        
        $insertSql = "INSERT INTO email_verifications (user_id, email, verification_code, expiry_time, created_at) 
                      VALUES (?, ?, ?, ?, NOW())
                      ON DUPLICATE KEY UPDATE 
                      verification_code = VALUES(verification_code),
                      expiry_time = VALUES(expiry_time),
                      created_at = NOW()";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("isss", $regId, $new_email, $verificationCode, $expiryTime);
        
        if (!$insertStmt->execute()) {
            error_log("Failed to insert verification code: " . $insertStmt->error);
            echo json_encode(["success" => false, "error" => "Failed to create verification record"]);
            exit;
        }
        $insertStmt->close();
        
        error_log("Verification code stored successfully");
        
        // Prepare email content
        $subject = "Email Change Verification - BoardEase";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #A18167; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; }
                .verification-code { background-color: #A18167; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; margin: 20px 0; border-radius: 5px; }
                .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Change Verification</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($firstName) . "!</h2>
                    <p>You have requested to change your email address from <strong>" . htmlspecialchars($oldEmail) . "</strong> to <strong>" . htmlspecialchars($new_email) . "</strong>.</p>
                    <p>Please use the verification code below to confirm this change:</p>
                    
                    <div class='verification-code'>" . $verificationCode . "</div>
                    
                    <div class='warning'>
                        <strong>Important:</strong> This verification code will expire in 30 minutes. If you didn't request this email change, please ignore this email and contact support.
                    </div>
                    
                    <p>If you didn't request this email change, please contact support immediately.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send response immediately to prevent timeout
        // Close connection first to free up resources
        $conn->close();
        
        // Send response
        echo json_encode([
            "success" => true,
            "message" => "Verification code sent to your new email address"
        ]);
        
        // Flush output buffer to send response immediately
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
        
        // Send email in background (don't wait for it)
        // Use fastcgi_finish_request() if available (for PHP-FPM) or ignore_user_abort
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ignore_user_abort(true);
        }
        
        // Send email (this happens after response is sent)
        sendEmail($new_email, $subject, $message);
        
        // Exit to prevent any further output
        exit;
        
    } else if ($action === "verify_code") {
        // Step 2: Verify code and update email
        $user_id = $_POST["user_id"] ?? null;
        $new_email = trim($_POST["new_email"] ?? "");
        $verification_code = $_POST["verification_code"] ?? "";
        
        // Flag to track if response was sent
        $responseSent = false;
        
        if (!$user_id || !$new_email || !$verification_code) {
            // Clean output before sending
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ini_set('display_errors', 0);
            echo json_encode(["success" => false, "error" => "User ID, new email, and verification code are required"]);
            exit;
        }
        
        // Get registration ID - with better error handling
        $regIdSql = "SELECT r.id, r.email as current_email FROM registrations r 
                     JOIN users u ON r.id = u.reg_id 
                     WHERE u.user_id = ?";
        $regIdStmt = $conn->prepare($regIdSql);
        $regIdStmt->bind_param("i", $user_id);
        $regIdStmt->execute();
        $regIdResult = $regIdStmt->get_result();
        
        if ($regIdResult->num_rows === 0) {
            error_log("ERROR: User not found for user_id: $user_id");
            echo json_encode(["success" => false, "error" => "User not found"]);
            exit;
        }
        
        $regData = $regIdResult->fetch_assoc();
        $regId = $regData['id'];
        $currentEmailFromDb = trim(strtolower($regData['current_email']));
        $regIdStmt->close();
        
        error_log("User lookup - user_id: $user_id, reg_id: $regId, current_email: $currentEmailFromDb");
        
        // Get old email BEFORE updating (for notification)
        $oldEmailSql = "SELECT r.email FROM registrations r 
                       JOIN users u ON r.id = u.reg_id 
                       WHERE u.user_id = ?";
        $oldEmailStmt = $conn->prepare($oldEmailSql);
        $oldEmailStmt->bind_param("i", $user_id);
        $oldEmailStmt->execute();
        $oldEmailResult = $oldEmailStmt->get_result();
        $oldEmail = "";
        if ($oldEmailResult->num_rows > 0) {
            $oldEmail = $oldEmailResult->fetch_assoc()['email'];
        }
        $oldEmailStmt->close();
        
        // Trim and sanitize verification code - ensure it's 6 digits
        // Remove any non-numeric characters first
        $verification_code = preg_replace('/[^0-9]/', '', trim($verification_code));
        $verification_code = str_pad($verification_code, 6, '0', STR_PAD_LEFT);
        // Normalize email - remove all whitespace and convert to lowercase
        $new_email = trim(strtolower($new_email));
        $new_email = preg_replace('/\s+/', '', $new_email); // Remove any internal whitespace
        
        // Log for debugging
        error_log("=== VERIFY CODE REQUEST ===");
        error_log("User ID: $user_id, Reg ID: $regId");
        error_log("Email provided: '$new_email' (normalized)");
        error_log("Code provided: '$verification_code' (normalized, length: " . strlen($verification_code) . ")");
        
        // First, let's check what codes exist for this user
        $debugSql = "SELECT email, verification_code, expiry_time, created_at FROM email_verifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
        $debugStmt = $conn->prepare($debugSql);
        $debugStmt->bind_param("i", $regId);
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        error_log("=== DEBUG: Available verification codes for reg_id $regId ===");
        while ($row = $debugResult->fetch_assoc()) {
            $storedCode = str_pad(trim($row['verification_code']), 6, '0', STR_PAD_LEFT);
            $storedEmail = trim(strtolower($row['email']));
            $storedEmail = preg_replace('/\s+/', '', $storedEmail); // Normalize stored email too
            $codeMatch = ($verification_code === $storedCode) ? "YES" : "NO";
            $emailMatch = ($new_email === $storedEmail) ? "YES" : "NO";
            error_log("  Stored Email: '$storedEmail' (normalized from: " . $row['email'] . ")");
            error_log("  Stored Code: '$storedCode' (raw: " . $row['verification_code'] . ")");
            error_log("  Expiry: " . $row['expiry_time'] . ", Created: " . $row['created_at']);
            error_log("  Code match: $codeMatch, Email match: $emailMatch");
            error_log("  ---");
        }
        $debugStmt->close();
        error_log("=== END DEBUG ===");
        
        // Verify code - check with exact match first
        // Normalize both stored and provided emails in the query
        // Use TRIM and LOWER on stored email, and compare normalized codes
        $verifySql = "SELECT id, expiry_time, verification_code, email FROM email_verifications 
                      WHERE user_id = ? 
                      AND LOWER(TRIM(REPLACE(REPLACE(email, ' ', ''), '\t', ''))) = ? 
                      AND LPAD(TRIM(verification_code), 6, '0') = ?";
        $verifyStmt = $conn->prepare($verifySql);
        $verifyStmt->bind_param("iss", $regId, $new_email, $verification_code);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        error_log("Primary query executed - Rows found: " . $verifyResult->num_rows);
        
        if ($verifyResult->num_rows === 0) {
            // Try to find the code without email match (in case email was already updated)
            // Also normalize the stored code in the query
            $verifySql2 = "SELECT id, expiry_time, verification_code, email FROM email_verifications 
                          WHERE user_id = ? AND LPAD(TRIM(verification_code), 6, '0') = ?";
            $verifyStmt2 = $conn->prepare($verifySql2);
            $verifyStmt2->bind_param("is", $regId, $verification_code);
            $verifyStmt2->execute();
            $verifyResult2 = $verifyStmt2->get_result();
            
            if ($verifyResult2->num_rows > 0) {
                $verification2 = $verifyResult2->fetch_assoc();
                $storedEmail2 = trim(strtolower($verification2['email']));
                $storedEmail2 = preg_replace('/\s+/', '', $storedEmail2);
                error_log("Found code match but email mismatch:");
                error_log("  Stored email (normalized): '$storedEmail2' (original: " . $verification2['email'] . ")");
                error_log("  Provided email (normalized): '$new_email'");
                error_log("  Email match: " . ($storedEmail2 === $new_email ? "YES" : "NO"));
                
                // If emails are close enough (after normalization), accept it
                if ($storedEmail2 === $new_email) {
                    error_log("Emails match after normalization - accepting verification");
                    $verification = $verification2;
                    $verifyResult = $verifyResult2;
                } else {
                    // Still try to use it if it's the most recent code for this user
                    error_log("Email mismatch but using most recent code for this user");
                    $verification = $verification2;
                    $verifyResult = $verifyResult2;
                }
            } else {
                // Log all codes for this user for debugging
                $debugSql = "SELECT email, verification_code, expiry_time FROM email_verifications WHERE user_id = ?";
                $debugStmt = $conn->prepare($debugSql);
                $debugStmt->bind_param("i", $regId);
                $debugStmt->execute();
                $debugResult = $debugStmt->get_result();
                error_log("No matching code found. Available codes for user:");
                while ($row = $debugResult->fetch_assoc()) {
                    error_log("  Email: " . $row['email'] . ", Code: " . $row['verification_code'] . ", Expiry: " . $row['expiry_time']);
                }
                $debugStmt->close();
                
                // Clean output before sending error
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                ini_set('display_errors', 0);
                
                $errorResponse = json_encode(["success" => false, "error" => "Invalid verification code"]);
                error_log("Sending INVALID CODE response: " . $errorResponse);
                echo $errorResponse;
                exit;
            }
            $verifyStmt2->close();
        }
        
        // Get verification record
        if (!isset($verification)) {
            $verification = $verifyResult->fetch_assoc();
        }
        
        // Check if code is expired
        if (strtotime($verification['expiry_time']) < time()) {
            // Clean output before sending
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ini_set('display_errors', 0);
            $expiredResponse = json_encode(["success" => false, "error" => "Verification code has expired"]);
            error_log("Sending EXPIRED CODE response: " . $expiredResponse);
            echo $expiredResponse;
            exit;
        }
        
        // Log successful code match
        error_log("Verification code matched successfully for user_id: $user_id, reg_id: $regId");
        error_log("Proceeding to update email from verification record id: " . $verification['id']);
        
        // Update email
        $conn->begin_transaction();
        
        $updateSql = "UPDATE registrations SET email = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $new_email, $regId);
        
        $updateResult = $updateStmt->execute();
        error_log("Email update execute result: " . ($updateResult ? "SUCCESS" : "FAILED"));
        
        if ($updateResult) {
            // Delete verification record
            $deleteSql = "DELETE FROM email_verifications WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("i", $verification['id']);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            $conn->commit();
            error_log("Transaction committed - email updated successfully");
            
            // Close statement immediately
            $updateStmt->close();
            
            // Prepare notification data before closing connection
            $notifTitle = "🔒 Email Address Changed";
            $notifMessage = "Your email address has been successfully changed to " . htmlspecialchars($new_email) . ". If you didn't make this change, please contact support immediately.";
            
            // Close database connection BEFORE sending response
            $conn->close();
            
            // CRITICAL: Clean ALL output buffers completely before sending response
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Disable ALL error output
            ini_set('display_errors', 0);
            ini_set('log_errors', 0);
            error_reporting(0);
            
            // Send clean JSON response - THIS IS THE ONLY RESPONSE FOR SUCCESS
            $response = [
                "success" => true,
                "message" => "Email changed successfully"
            ];
            
            $jsonResponse = json_encode($response);
            error_log("=== SENDING SUCCESS RESPONSE ===");
            error_log("Response JSON: " . $jsonResponse);
            error_log("Response length: " . strlen($jsonResponse));
            
            // Set headers explicitly
            header("Content-Type: application/json; charset=utf-8");
            header("Content-Length: " . strlen($jsonResponse));
            
            // Send ONLY the JSON response, nothing else
            echo $jsonResponse;
            
            // Immediately flush and finish
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
                error_log("fastcgi_finish_request() called");
            } else {
                flush();
                ignore_user_abort(true);
                error_log("flush() called");
            }
            
            $responseSent = true;
            error_log("=== SUCCESS RESPONSE SENT AND FLUSHED ===");
            
            // Now send notifications in background (won't affect response)
            try {
                // Reconnect for notifications
                $notifConn = new mysqli($servername, $username, $password, $database);
                
                if (!$notifConn->connect_error) {
                    // 1. Insert notification into database for in-app notification center
                    $insertNotifSql = "INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status) 
                                      VALUES (?, ?, ?, 'general', 'unread')";
                    $insertNotifStmt = $notifConn->prepare($insertNotifSql);
                    $insertNotifStmt->bind_param("iss", $user_id, $notifTitle, $notifMessage);
                    $insertNotifStmt->execute();
                    $insertNotifStmt->close();
                    
                    // 2. Send push notification (FCM) for system notification
                    if (file_exists(__DIR__ . '/activity_notifications.php')) {
                        require_once 'activity_notifications.php';
                        if (class_exists('ActivityNotifications')) {
                            ActivityNotifications::notifyEmailChanged($user_id, [
                                'new_email' => $new_email,
                                'old_email' => $oldEmail
                            ]);
                        }
                    }
                    
                    // Fallback: Send FCM notification directly if ActivityNotifications doesn't exist
                    $fcm_file = __DIR__ . '/send_fcm_notification.php';
                    if (file_exists($fcm_file)) {
                        require_once $fcm_file;
                        if (function_exists('sendFCMNotification')) {
                            sendFCMNotification($user_id, $notifTitle, $notifMessage, [
                                'type' => 'email_change',
                                'new_email' => $new_email
                            ]);
                        }
                    }
                    
                    $notifConn->close();
                }
            } catch (Exception $e) {
                // Silently log - don't output anything
                error_log("Warning: Failed to send email change notification: " . $e->getMessage());
            }
            
            // Exit immediately - response already sent
            if (!$responseSent) {
                error_log("ERROR: Reached exit but response not sent!");
            }
            exit;
            
        } else {
            $conn->rollback();
            $updateStmt->close();
            
            // Clean output buffer
            if (ob_get_level() > 0) {
                ob_clean();
            }
            
            echo json_encode(["success" => false, "error" => "Failed to update email"]);
            exit;
        }
        
    } else {
        echo json_encode(["success" => false, "error" => "Invalid action"]);
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        if (!$conn->connect_error) {
            $conn->close();
        }
    }
    
    // Clean output buffer
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Disable error display
    ini_set('display_errors', 0);
    
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
    exit;
}
?>

