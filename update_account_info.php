<?php
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

// Get parameters from POST request
$user_id = $_POST["user_id"] ?? null;
$email = $_POST["email"] ?? null; // Optional - only needed if changing email
$current_password = $_POST["current_password"] ?? "";
$new_password = $_POST["new_password"] ?? "";

// Validate required fields - user_id is always required
if (!$user_id) {
    echo json_encode(["success" => false, "error" => "User ID is required"]);
    exit;
}

try {
    // Clean output buffer at the start
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $conn->begin_transaction();
    
    $passwordChanged = false;
    $emailChanged = false;
    $oldEmail = null;
    $passwordVerificationPassed = false; // Flag to track if password verification passed
    
    // Check if password change is requested
    if (!empty($new_password)) {
        if (empty($current_password)) {
            // Rollback transaction before sending error
            $conn->rollback();
            
            // Clean output buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            echo json_encode(["success" => false, "error" => "Current password is required to change password"]);
            exit;
        }
        
        // Verify current password
        // Trim passwords to handle any whitespace issues
        $current_password = trim($current_password);
        $new_password = trim($new_password);
        
        $checkSql = "SELECT r.password FROM registrations r 
                     JOIN users u ON r.id = u.reg_id 
                     WHERE u.user_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $row = $checkResult->fetch_assoc();
            $storedPassword = $row["password"];
            
            // Debug logging
            error_log("Password verification - User ID: $user_id");
            error_log("Stored password length: " . strlen($storedPassword));
            error_log("Current password length: " . strlen($current_password));
            
            // Check if password is hashed or plain text
            $isHashed = password_get_info($storedPassword)['algo'] !== null;
            error_log("Password is hashed: " . ($isHashed ? "YES" : "NO"));
            
            $passwordValid = false;
            
            if ($isHashed) {
                // Password is hashed, use password_verify
                $passwordValid = password_verify($current_password, $storedPassword);
                error_log("Password verification result: " . ($passwordValid ? "VALID" : "INVALID"));
                if (!$passwordValid) {
                    // Try with trimmed version in case of whitespace issues
                    $passwordValid = password_verify(trim($current_password), $storedPassword);
                    if ($passwordValid) {
                        error_log("Password verification VALID after trimming whitespace");
                    }
                }
            } else {
                // Password is plain text, compare directly (trim both)
                $storedPasswordTrimmed = trim($storedPassword);
                $passwordValid = ($current_password === $storedPasswordTrimmed);
                error_log("Plain text password comparison: " . ($passwordValid ? "MATCH" : "NO MATCH"));
                error_log("  Current password (trimmed): '" . $current_password . "' (length: " . strlen($current_password) . ")");
                error_log("  Stored password (trimmed): '" . $storedPasswordTrimmed . "' (length: " . strlen($storedPasswordTrimmed) . ")");
            }
            
            if (!$passwordValid) {
                // Rollback transaction before sending error
                $conn->rollback();
                $checkStmt->close();
                
                // Clean output buffer completely
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // Disable all error output
                ini_set('display_errors', 0);
                ini_set('log_errors', 0);
                error_reporting(0);
                
                error_log("Password verification FAILED - rolling back transaction for user_id: $user_id");
                
                // Send ONLY the error response
                $errorResponse = json_encode(["success" => false, "error" => "Current password is incorrect"]);
                header("Content-Type: application/json; charset=utf-8");
                header("Content-Length: " . strlen($errorResponse));
                echo $errorResponse;
                
                // Flush and exit immediately
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    flush();
                }
                
                // Close connection and exit
                if (isset($conn) && !$conn->connect_error) {
                    $conn->close();
                }
                exit(1); // Exit with error code
            }
            
            error_log("Password verification SUCCESS for user_id: $user_id");
            $passwordVerificationPassed = true; // Mark verification as passed
            $checkStmt->close();
        } else {
            // Rollback transaction before sending error
            $conn->rollback();
            $checkStmt->close();
            
            // Clean output buffer completely
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Disable all error output
            ini_set('display_errors', 0);
            ini_set('log_errors', 0);
            error_reporting(0);
            
            error_log("User not found for password change - user_id: $user_id");
            
            // Send ONLY the error response
            $errorResponse = json_encode(["success" => false, "error" => "User not found"]);
            header("Content-Type: application/json; charset=utf-8");
            header("Content-Length: " . strlen($errorResponse));
            echo $errorResponse;
            
            // Flush and exit immediately
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                flush();
            }
            
            // Close connection and exit
            if (isset($conn) && !$conn->connect_error) {
                $conn->close();
            }
            exit(1); // Exit with error code
        }
        
        // Update password - ONLY if verification explicitly passed
        if (!$passwordVerificationPassed) {
            // This should never happen, but add safety check
            $conn->rollback();
            
            // Clean output buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            error_log("CRITICAL ERROR: Attempted to update password without verification! user_id: $user_id");
            echo json_encode(["success" => false, "error" => "Password verification required"]);
            exit(1);
        }
        
        error_log("Proceeding with password update - verification passed for user_id: $user_id");
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $updatePasswordSql = "UPDATE registrations r 
                              JOIN users u ON r.id = u.reg_id 
                              SET r.password = ? 
                              WHERE u.user_id = ?";
        $updatePasswordStmt = $conn->prepare($updatePasswordSql);
        $updatePasswordStmt->bind_param("si", $hashedPassword, $user_id);
        
        if (!$updatePasswordStmt->execute()) {
            // Rollback on update failure
            $conn->rollback();
            $updatePasswordStmt->close();
            
            // Clean output buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            error_log("Password update failed: " . $updatePasswordStmt->error);
            echo json_encode(["success" => false, "error" => "Failed to update password"]);
            exit;
        }
        
        $updatePasswordStmt->close();
        $passwordChanged = true;
        error_log("Password update successful for user_id: $user_id");
    }
    
    // Check if email is being changed (only if email is provided)
    if (!empty($email)) {
        $checkEmailSql = "SELECT r.email FROM registrations r 
                         JOIN users u ON r.id = u.reg_id 
                         WHERE u.user_id = ?";
        $checkEmailStmt = $conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param("i", $user_id);
        $checkEmailStmt->execute();
        $emailResult = $checkEmailStmt->get_result();
        if ($emailResult->num_rows > 0) {
            $oldEmail = $emailResult->fetch_assoc()['email'];
        }
        $checkEmailStmt->close();
        $emailChanged = ($oldEmail && $oldEmail !== $email);
        
        // Update email only if it's different
        if ($emailChanged) {
            $updateSql = "UPDATE registrations r 
                          JOIN users u ON r.id = u.reg_id 
                          SET r.email = ? 
                          WHERE u.user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $email, $user_id);
            $updateStmt->execute();
            $updateStmt->close();
        }
    } else {
        // No email provided, so no email change
        $emailChanged = false;
    }
    
    // Commit transaction
    $conn->commit();
    error_log("Transaction committed - password changed: " . ($passwordChanged ? "YES" : "NO") . ", email changed: " . ($emailChanged ? "YES" : "NO"));
    
    // Prepare notification data before closing connection
    $passwordNotifTitle = "🔒 Password Changed";
    $passwordNotifMessage = "Your password has been successfully changed. If you didn't make this change, please contact support immediately and reset your password.";
    
    $emailNotifTitle = "🔒 Email Address Changed";
    $emailNotifMessage = "Your email address has been successfully changed from " . htmlspecialchars($oldEmail) . " to " . htmlspecialchars($email) . ". If you didn't make this change, please contact support immediately.";
    
    // Close database connection BEFORE sending response
    $conn->close();
    
    // Send response FIRST - clean output buffer completely
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Disable ALL error output
    ini_set('display_errors', 0);
    ini_set('log_errors', 0);
    error_reporting(0);
    
    // Determine success message and send response
    if ($passwordChanged || $emailChanged) {
        if ($passwordChanged && $emailChanged) {
            $message = "Password and email updated successfully";
        } else if ($passwordChanged) {
            $message = "Password changed successfully";
        } else if ($emailChanged) {
            $message = "Email changed successfully";
        } else {
            $message = "Account information updated successfully";
        }
        
        $response = [
            "success" => true,
            "message" => $message
        ];
        
        $jsonResponse = json_encode($response);
        error_log("=== SENDING SUCCESS RESPONSE ===");
        error_log("Response JSON: " . $jsonResponse);
        
        // Set headers explicitly
        header("Content-Type: application/json; charset=utf-8");
        header("Content-Length: " . strlen($jsonResponse));
        
        // Send ONLY the JSON response
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
        
        error_log("=== SUCCESS RESPONSE SENT ===");
        
    } else {
        // Nothing was changed
        $errorResponse = json_encode(["success" => false, "error" => "No changes to update"]);
        echo $errorResponse;
        exit;
    }
    
    // Now send notifications in background (won't affect response)
    try {
        // Reconnect for notifications
        $notifConn = new mysqli($servername, $username, $password, $database);
        
        if (!$notifConn->connect_error) {
            // Send notifications for password change
            if ($passwordChanged) {
                // 1. Insert notification into database for in-app notification center
                $insertNotifSql = "INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status) 
                                  VALUES (?, ?, ?, 'general', 'unread')";
                $insertNotifStmt = $notifConn->prepare($insertNotifSql);
                $insertNotifStmt->bind_param("iss", $user_id, $passwordNotifTitle, $passwordNotifMessage);
                $insertNotifStmt->execute();
                $insertNotifStmt->close();
                
                // 2. Send push notification (FCM) for system notification
                if (file_exists(__DIR__ . '/activity_notifications.php')) {
                    require_once 'activity_notifications.php';
                    if (class_exists('ActivityNotifications')) {
                        ActivityNotifications::notifyPasswordChanged($user_id, []);
                    }
                }
                
                // Fallback: Send FCM notification directly if ActivityNotifications doesn't exist
                $fcm_file = __DIR__ . '/send_fcm_notification.php';
                if (file_exists($fcm_file)) {
                    require_once $fcm_file;
                    if (function_exists('sendFCMNotification')) {
                        sendFCMNotification($user_id, $passwordNotifTitle, $passwordNotifMessage, [
                            'type' => 'password_change'
                        ]);
                    }
                }
            }
            
            // Send notifications for email change
            if ($emailChanged) {
                // 1. Insert notification into database for in-app notification center
                $insertNotifSql = "INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status) 
                                  VALUES (?, ?, ?, 'general', 'unread')";
                $insertNotifStmt = $notifConn->prepare($insertNotifSql);
                $insertNotifStmt->bind_param("iss", $user_id, $emailNotifTitle, $emailNotifMessage);
                $insertNotifStmt->execute();
                $insertNotifStmt->close();
                
                // 2. Send push notification (FCM) for system notification
                if (file_exists(__DIR__ . '/activity_notifications.php')) {
                    require_once 'activity_notifications.php';
                    if (class_exists('ActivityNotifications')) {
                        ActivityNotifications::notifyEmailChanged($user_id, [
                            'new_email' => $email,
                            'old_email' => $oldEmail
                        ]);
                    }
                }
                
                // Fallback: Send FCM notification directly if ActivityNotifications doesn't exist
                $fcm_file = __DIR__ . '/send_fcm_notification.php';
                if (file_exists($fcm_file)) {
                    require_once $fcm_file;
                    if (function_exists('sendFCMNotification')) {
                        sendFCMNotification($user_id, $emailNotifTitle, $emailNotifMessage, [
                            'type' => 'email_change',
                            'new_email' => $email,
                            'old_email' => $oldEmail
                        ]);
                    }
                }
            }
            
            $notifConn->close();
        }
    } catch (Exception $e) {
        // Silently log - don't output anything
        error_log("Warning: Failed to send notifications: " . $e->getMessage());
    }
    
    // Exit immediately
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on any exception
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
        error_log("Transaction rolled back due to exception: " . $e->getMessage());
    }
    
    // Clean output buffer
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Disable error output
    ini_set('display_errors', 0);
    
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
} finally {
    if (isset($conn) && !$conn->connect_error) {
        $conn->close();
    }
}
?>
