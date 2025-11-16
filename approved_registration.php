<?php
// approve_registration.php

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
    $servername = "localhost";
    $username   = "boardease";
    $password   = "boardease";
    $dbname     = "boardease2";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get POST data
    $registrationId = $_POST['registration_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'approve' or 'reject'

    if (!$registrationId || !$action) {
        throw new Exception("Missing required parameters");
    }

    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception("Invalid action. Must be 'approve' or 'reject'");
    }

    // Start transaction
    $conn->autocommit(FALSE);
    $conn->begin_transaction();
    error_log("=== APPROVAL PROCESS START ===");
    error_log("Registration ID: $registrationId, Action: $action");
    error_log("Database: $dbname, Table: 'registrations'");
    error_log("MySQL version: " . $conn->server_info);

    try {
        // First, verify which table we're using and that the registration exists
        error_log("Looking for registration ID $registrationId in 'registrations' table");
        
        // Get registration details - ONLY 'pending' status can be approved
        $sql = "SELECT id, status, email, first_name, last_name FROM registrations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare SELECT statement: " . $conn->error . " (Table: registrations)");
        }
        $stmt->bind_param("i", $registrationId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute SELECT: " . $stmt->error);
        }
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            // Check if it exists in the old 'registration' table
            $checkOldSql = "SELECT reg_id, status, email FROM registration WHERE reg_id = ?";
            $checkOldStmt = $conn->prepare($checkOldSql);
            if ($checkOldStmt) {
                $checkOldStmt->bind_param("i", $registrationId);
                $checkOldStmt->execute();
                $checkOldResult = $checkOldStmt->get_result();
                if ($checkOldResult->num_rows > 0) {
                    $oldRow = $checkOldResult->fetch_assoc();
                    $checkOldStmt->close();
                    throw new Exception("Registration ID {$registrationId} found in OLD 'registration' table (not 'registrations'). Status: {$oldRow['status']}. Please check which table is being used.");
                }
                $checkOldStmt->close();
            }
            throw new Exception("Registration ID {$registrationId} not found in 'registrations' table. Please verify the registration ID.");
        }
        
        $registration = $result->fetch_assoc();
        error_log("Found registration: ID={$registration['id']}, Status={$registration['status']}, Email={$registration['email']}");
        $stmt->close();
        
        // Check if status is pending
        if ($registration['status'] !== 'pending') {
            throw new Exception("Registration ID {$registrationId} has status '{$registration['status']}'. Only registrations with status 'pending' can be approved.");
        }

        if ($action === 'approve') {
            // Check if user already exists
            $sql = "SELECT user_id FROM users WHERE reg_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $registrationId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // User already exists, just update registration status
                $sql = "UPDATE registrations SET status = 'approved' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Failed to prepare UPDATE statement: " . $conn->error);
                }
                $stmt->bind_param("i", $registrationId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update registration status: " . $stmt->error);
                }
                $stmt->close();
                $message = "Registration already approved (user account already exists)";
                error_log("Registration ID $registrationId: User already exists, status updated to approved");
            } else {
                // Update registration status to approved
                // First, verify the current status within the transaction
                $checkBeforeSql = "SELECT id, status FROM registrations WHERE id = ? FOR UPDATE";
                $checkBeforeStmt = $conn->prepare($checkBeforeSql);
                $checkBeforeStmt->bind_param("i", $registrationId);
                $checkBeforeStmt->execute();
                $checkBeforeResult = $checkBeforeStmt->get_result();
                $checkBeforeRow = $checkBeforeResult->fetch_assoc();
                error_log("BEFORE UPDATE: Registration ID $registrationId current status: " . $checkBeforeRow['status']);
                $checkBeforeStmt->close();
                
                if ($checkBeforeRow['status'] !== 'pending') {
                    throw new Exception("Cannot approve registration ID $registrationId: Current status is '" . $checkBeforeRow['status'] . "', expected 'pending'");
                }
                
                // Update the status - use explicit table name and verify
                error_log("Executing UPDATE on 'registrations' table for ID: $registrationId");
                $sql = "UPDATE `registrations` SET `status` = 'approved' WHERE `id` = ?";
                error_log("SQL: $sql");
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    error_log("ERROR preparing UPDATE: " . $conn->error);
                    throw new Exception("Failed to prepare UPDATE statement: " . $conn->error);
                }
                $stmt->bind_param("i", $registrationId);
                $executed = $stmt->execute();
                if (!$executed) {
                    error_log("ERROR executing UPDATE: " . $stmt->error . " (MySQL error: " . $conn->error . ")");
                    throw new Exception("Failed to execute UPDATE: " . $stmt->error . " (MySQL error: " . $conn->error . ")");
                }
                $affectedRows = $stmt->affected_rows;
                $mysqlError = $conn->error;
                $mysqlErrno = $conn->errno;
                error_log("UPDATE RESULT: Affected rows = $affectedRows, MySQL errno = $mysqlErrno" . ($mysqlError ? ", MySQL error = " . $mysqlError : ""));
                $stmt->close();
                
                // Check if UPDATE affected any rows
                if ($affectedRows === 0) {
                    // Check status again to see what happened
                    $checkAfterSql = "SELECT id, status FROM `registrations` WHERE id = ?";
                    $checkAfterStmt = $conn->prepare($checkAfterSql);
                    $checkAfterStmt->bind_param("i", $registrationId);
                    $checkAfterStmt->execute();
                    $checkAfterResult = $checkAfterStmt->get_result();
                    if ($checkAfterResult->num_rows > 0) {
                        $checkAfterRow = $checkAfterResult->fetch_assoc();
                        error_log("UPDATE affected 0 rows. Current status: '" . $checkAfterRow['status'] . "'");
                        $checkAfterStmt->close();
                        // If status is already 'approved', that's okay - just continue
                        if ($checkAfterRow['status'] === 'approved') {
                            error_log("Status is already 'approved' - skipping update");
                        } else {
                            throw new Exception("UPDATE affected 0 rows. Current status: '" . $checkAfterRow['status'] . "'. Registration may have been modified by another process or status check in WHERE clause failed.");
                        }
                    } else {
                        $checkAfterStmt->close();
                        throw new Exception("UPDATE affected 0 rows and registration not found.");
                    }
                }
                
                // Verify the update within the same transaction
                $verifySql = "SELECT id, status FROM `registrations` WHERE id = ?";
                $verifyStmt = $conn->prepare($verifySql);
                $verifyStmt->bind_param("i", $registrationId);
                $verifyStmt->execute();
                $verifyResult = $verifyStmt->get_result();
                if ($verifyResult->num_rows > 0) {
                    $verifyRow = $verifyResult->fetch_assoc();
                    $actualStatus = $verifyRow['status'];
                    error_log("VERIFICATION (within transaction): Registration ID $registrationId, Status = '$actualStatus'");
                    
                    // Check if status was actually updated
                    if ($actualStatus !== 'approved') {
                        error_log("ERROR: Status verification failed! Expected 'approved' but got '$actualStatus'");
                        error_log("This means the UPDATE did not work. Possible causes:");
                        error_log("  1. Database trigger reverting the change");
                        error_log("  2. Constraint preventing the update");
                        error_log("  3. Wrong table being updated");
                        $verifyStmt->close();
                        throw new Exception("UPDATE failed: Status is still '$actualStatus' instead of 'approved'. Affected rows was: $affectedRows. Please check database logs.");
                    } else {
                        error_log("✓ VERIFIED: Status is now 'approved'");
                    }
                } else {
                    $verifyStmt->close();
                    error_log("ERROR: Registration ID $registrationId not found after UPDATE");
                    throw new Exception("UPDATE verification failed: Registration ID $registrationId not found after update");
                }
                $verifyStmt->close();
                
                error_log("SUCCESS: Registration ID $registrationId status updated to 'approved' (verified, affected_rows: $affectedRows)");

                // Create user account in users table with correct structure
                // Check if user already exists first
                $checkUserSql = "SELECT user_id FROM users WHERE reg_id = ?";
                $checkUserStmt = $conn->prepare($checkUserSql);
                $checkUserStmt->bind_param("i", $registrationId);
                $checkUserStmt->execute();
                $checkUserResult = $checkUserStmt->get_result();
                
                if ($checkUserResult->num_rows > 0) {
                    $existingUser = $checkUserResult->fetch_assoc();
                    $insertedUserId = $existingUser['user_id'];
                    error_log("User account already exists: user_id=$insertedUserId, reg_id=$registrationId");
                    $checkUserStmt->close();
                } else {
                    $checkUserStmt->close();
                    
                    // user_id is AUTO_INCREMENT, so we don't specify it
                    // status enum values: 'Active' or 'Inactive'
                    $sql = "INSERT INTO `users` (`reg_id`, `profile_picture`, `status`) 
                            VALUES (?, ?, 'Active')";
                    error_log("Preparing INSERT into users table: reg_id=$registrationId");
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        error_log("ERROR preparing INSERT: " . $conn->error);
                        throw new Exception("Failed to prepare INSERT statement: " . $conn->error);
                    }
                    $profile_picture = null;
                    $stmt->bind_param("is", 
                        $registrationId, 
                        $profile_picture // profile_picture is null initially
                    );
                    error_log("Executing INSERT into users table...");
                    $executed = $stmt->execute();
                    if (!$executed) {
                        $error = $stmt->error;
                        $mysqlError = $conn->error;
                        error_log("ERROR executing INSERT: $error (MySQL error: $mysqlError)");
                        throw new Exception("Failed to create user account: $error (MySQL error: $mysqlError, Registration ID: $registrationId)");
                    }
                    $insertedUserId = $conn->insert_id;
                    $affectedRowsInsert = $stmt->affected_rows;
                    error_log("INSERT RESULT: user_id=$insertedUserId, affected_rows=$affectedRowsInsert, MySQL errno=" . $conn->errno);
                    
                    if ($insertedUserId <= 0) {
                        error_log("WARNING: insert_id is 0 or negative: $insertedUserId");
                        throw new Exception("Failed to get inserted user_id. INSERT may have failed. MySQL error: " . $conn->error);
                    }
                    
                    // Verify the user was created
                    $verifyUserSql = "SELECT user_id, reg_id, status FROM users WHERE user_id = ?";
                    $verifyUserStmt = $conn->prepare($verifyUserSql);
                    $verifyUserStmt->bind_param("i", $insertedUserId);
                    $verifyUserStmt->execute();
                    $verifyUserResult = $verifyUserStmt->get_result();
                    if ($verifyUserResult->num_rows > 0) {
                        $verifyUserRow = $verifyUserResult->fetch_assoc();
                        error_log("✓ VERIFIED: User account created - user_id=" . $verifyUserRow['user_id'] . ", reg_id=" . $verifyUserRow['reg_id'] . ", status=" . $verifyUserRow['status']);
                    } else {
                        error_log("ERROR: User account not found after INSERT! user_id=$insertedUserId");
                        throw new Exception("User account was not created. INSERT returned user_id=$insertedUserId but user not found in database.");
                    }
                    $verifyUserStmt->close();
                    $stmt->close();
                    
                    error_log("User account created successfully: user_id=$insertedUserId, reg_id=$registrationId");
                }
                
                // Store user info for email/notification AFTER transaction commit
                $userName = trim($registration['first_name'] . ' ' . $registration['middle_name'] . ' ' . $registration['last_name']);
                $userEmail = $registration['email'];
                $userRole = $registration['role'];
                $message = "Registration approved successfully";
            }
        } else {
            // Update registration status to rejected
            $sql = "UPDATE `registrations` SET `status` = 'rejected' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare UPDATE statement for rejection: " . $conn->error);
            }
            $stmt->bind_param("i", $registrationId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute UPDATE for rejection: " . $stmt->error);
            }
            $stmt->close();
            $message = "Registration rejected successfully";
            
            // Store user info for email/notification AFTER transaction commit
            $userName = trim($registration['first_name'] . ' ' . $registration['middle_name'] . ' ' . $registration['last_name']);
            $userEmail = $registration['email'];
            $rejectionReason = $_POST['reason'] ?? '';
        }

        // Commit transaction FIRST - before sending emails/notifications
        if (!$conn->commit()) {
            $error = $conn->error;
            error_log("ERROR: Failed to commit transaction: " . $error);
            $conn->rollback();
            $conn->autocommit(TRUE);
            throw new Exception("Failed to commit transaction: " . $error);
        }
        
        // Re-enable autocommit
        $conn->autocommit(TRUE);
        
        error_log("SUCCESS: Transaction committed for registration ID $registrationId");
        
        // Verify user was created AFTER commit (if approval action)
        if ($action === 'approve' && isset($insertedUserId) && $insertedUserId > 0) {
            $verifyUserAfterCommit = "SELECT user_id, reg_id, status FROM users WHERE user_id = ?";
            $verifyUserAfterStmt = $conn->prepare($verifyUserAfterCommit);
            $verifyUserAfterStmt->bind_param("i", $insertedUserId);
            $verifyUserAfterStmt->execute();
            $verifyUserAfterResult = $verifyUserAfterStmt->get_result();
            if ($verifyUserAfterResult->num_rows > 0) {
                $verifyUserAfterRow = $verifyUserAfterResult->fetch_assoc();
                error_log("✓ FINAL VERIFICATION: User exists after commit - user_id=" . $verifyUserAfterRow['user_id'] . ", reg_id=" . $verifyUserAfterRow['reg_id']);
            } else {
                error_log("✗ ERROR: User NOT found after commit! user_id=$insertedUserId");
            }
            $verifyUserAfterStmt->close();
        }
        
        // Get admin ID from session (if available)
        $admin_id = $_SESSION['admin_id'] ?? null;
        
        // Send approval email notification (AFTER transaction commit)
        if ($action === 'approve' && isset($userEmail) && isset($userName)) {
            try {
                $emailSubject = "🎉 Your BoardEase Account Has Been Approved!";
                $emailMessage = getAccountApprovalEmailTemplate($userName, $userEmail, $userRole);
                
                $emailSent = sendEmail($userEmail, $emailSubject, $emailMessage);
                
                if ($emailSent) {
                    error_log("Approval email sent successfully to: " . $userEmail);
                    $message = "Registration approved successfully and notification email sent";
                } else {
                    error_log("Failed to send approval email to: " . $userEmail);
                    $message = "Registration approved successfully but email notification failed";
                }
            } catch (Exception $e) {
                error_log("Warning: Failed to send approval email: " . $e->getMessage());
                $message = "Registration approved successfully but email notification failed";
            }
            
            // Send in-app notification to user (AFTER transaction commit)
            if (isset($insertedUserId) && $insertedUserId > 0) {
                try {
                    require_once 'activity_notifications.php';
                    ActivityNotifications::notifyRegistrationApproved($insertedUserId, [
                        'user_name' => $userName,
                        'role' => $userRole
                    ]);
                    error_log("In-app notification sent to user_id: $insertedUserId for registration approval");
                } catch (Exception $e) {
                    error_log("Warning: Failed to send in-app notification for registration approval: " . $e->getMessage());
                }
            }
            
            // Log admin activity (AFTER transaction commit)
            if ($admin_id) {
                try {
                    require_once 'log_admin_activity.php';
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    logAdminActivity(
                        $admin_id,
                        'user_approved',
                        'User registration approved: ' . $userName,
                        'Registration ID: ' . $registrationId . ', Email: ' . $userEmail . ', Role: ' . $userRole,
                        $ip_address,
                        $user_agent
                    );
                    error_log("Admin activity logged: user_approved for registration ID $registrationId");
                } catch (Exception $e) {
                    error_log("Warning: Failed to log admin activity for user approval: " . $e->getMessage());
                }
            }
        }

        $response = array(
            "success" => true,
            "message" => $message,
            "action" => $action,
            "registration_id" => $registrationId,
            "registration_email" => $registration['email'],
            "user_created" => isset($insertedUserId) && $insertedUserId > 0 ? true : false,
            "user_id" => isset($insertedUserId) ? $insertedUserId : null
        );

        error_log("Registration $action successful: ID $registrationId, Email: " . $registration['email'] . ", User created: " . (isset($insertedUserId) && $insertedUserId > 0 ? "Yes (user_id=$insertedUserId)" : "No"));
        
        // Send rejection email/notification AFTER commit (if rejection)
        if ($action === 'reject' && isset($userEmail) && isset($userName)) {
            try {
                $emailSubject = "Account Registration Update - BoardEase";
                $emailMessage = getAccountRejectionEmailTemplate($userName, $userEmail, $rejectionReason);
                
                $emailSent = sendEmail($userEmail, $emailSubject, $emailMessage);
                
                if ($emailSent) {
                    error_log("Rejection email sent successfully to: " . $userEmail);
                    $message = "Registration rejected successfully and notification email sent";
                } else {
                    error_log("Failed to send rejection email to: " . $userEmail);
                    $message = "Registration rejected successfully but email notification failed";
                }
            } catch (Exception $e) {
                error_log("Warning: Failed to send rejection email: " . $e->getMessage());
                $message = "Registration rejected successfully but email notification failed";
            }
            
            // Send in-app notification to user (if user entry exists)
            try {
                require_once 'activity_notifications.php';
                $getUserIdSql = "SELECT user_id FROM users WHERE reg_id = ?";
                $getUserIdStmt = $conn->prepare($getUserIdSql);
                $getUserIdStmt->bind_param("i", $registrationId);
                $getUserIdStmt->execute();
                $userIdResult = $getUserIdStmt->get_result();
                if ($userIdResult->num_rows > 0) {
                    $userIdRow = $userIdResult->fetch_assoc();
                    $userId = $userIdRow['user_id'];
                    ActivityNotifications::notifyRegistrationRejected($userId, [
                        'user_name' => $userName,
                        'reason' => $rejectionReason
                    ]);
                    error_log("In-app notification sent to user_id: $userId for registration rejection");
                }
                $getUserIdStmt->close();
            } catch (Exception $e) {
                error_log("Warning: Failed to send in-app notification for registration rejection: " . $e->getMessage());
            }
            
            // Log admin activity (AFTER transaction commit)
            if (isset($admin_id) && $admin_id) {
                try {
                    require_once 'log_admin_activity.php';
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    logAdminActivity(
                        $admin_id,
                        'user_rejected',
                        'User registration rejected: ' . $userName,
                        'Registration ID: ' . $registrationId . ', Email: ' . $userEmail . ', Reason: ' . ($rejectionReason ?? 'No reason provided'),
                        $ip_address,
                        $user_agent
                    );
                    error_log("Admin activity logged: user_rejected for registration ID $registrationId");
                } catch (Exception $e) {
                    error_log("Warning: Failed to log admin activity for user rejection: " . $e->getMessage());
                }
            }
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Transaction error in approval process: " . $e->getMessage());
        throw $e;
    }

    // Send response
    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Approve registration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Make sure connection is still open before closing
    if (isset($conn) && $conn) {
        // Rollback any open transaction
        @$conn->rollback();
        @$conn->autocommit(TRUE);
    }
    
    $response = array(
        "success" => false,
        "message" => "Error processing registration: " . $e->getMessage(),
        "error_details" => $e->getMessage()
    );
    http_response_code(400); // Set error status code
    echo json_encode($response);
    exit;
}

// Close connection only if it exists and is still open
if (isset($conn) && $conn) {
    $conn->close();
}
?>
