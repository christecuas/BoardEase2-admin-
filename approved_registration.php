<?php
// approve_registration.php

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
    $conn->begin_transaction();

    try {
        // Get registration details
        $sql = "SELECT * FROM registrations WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $registrationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Registration not found or already processed");
        }
        
        $registration = $result->fetch_assoc();
        $stmt->close();

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
                $stmt->bind_param("i", $registrationId);
                $stmt->execute();
                $stmt->close();
                $message = "Registration already approved";
            } else {
                // Update registration status to approved
                $sql = "UPDATE registrations SET status = 'approved' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $registrationId);
                $stmt->execute();
                $stmt->close();

                // Create user account in users table with correct structure
                $sql = "INSERT INTO users (reg_id, user_id, profile_picture, status) 
                        VALUES (?, ?, ?, 'Active')";
                $stmt = $conn->prepare($sql);
                $profile_picture = null;
                $stmt->bind_param("iss", 
                    $registrationId, 
                    $registration['email'], // user_id is the email
                    $profile_picture // profile_picture is null initially
                );
                $stmt->execute();
                $stmt->close();
                
                // Send approval email notification
                $userName = trim($registration['first_name'] . ' ' . $registration['middle_name'] . ' ' . $registration['last_name']);
                $userEmail = $registration['email'];
                $userRole = $registration['role'];
                
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
            }
        } else {
            // Update registration status to rejected
            $sql = "UPDATE registrations SET status = 'rejected' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $registrationId);
            $stmt->execute();
            $stmt->close();

            // Send rejection email notification
            $userName = trim($registration['first_name'] . ' ' . $registration['middle_name'] . ' ' . $registration['last_name']);
            $userEmail = $registration['email'];
            $rejectionReason = $_POST['reason'] ?? '';
            
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
        }

        // Commit transaction
        $conn->commit();

        $response = array(
            "success" => true,
            "message" => $message,
            "action" => $action,
            "registration_id" => $registrationId
        );

        error_log("Registration $action: ID $registrationId, Email: " . $registration['email']);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Approve registration error: " . $e->getMessage());
    $response = array(
        "success" => false,
        "message" => "Error processing registration: " . $e->getMessage()
    );
    echo json_encode($response);
}

$conn->close();
?>
