<?php
// email_verification.php - Handle email verification operations

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Include email configuration
require_once 'email_config.php';

// Log the request for debugging
error_log("Email verification request received at " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));

try {
    // Database connection
    $servername = "localhost";
    $username   = "boardease";
    $password   = "boardease";
    $dbname     = "boardease2";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        error_log("DB Connection failed: " . $conn->connect_error);
        $response = array(
            "success" => false,
            "message" => "Database connection failed."
        );
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? null;
    $email = $_POST['email'] ?? null;

    if (!$action || !$email) {
        $response = array(
            "success" => false,
            "message" => "Action and email are required."
        );
        echo json_encode($response);
        exit;
    }

    // Sanitize email
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if ($action === "resend_code") {
        error_log("Resend code request for email: " . $email);
        
        // Check if user exists and is unverified
        $stmt = $conn->prepare("SELECT id, first_name, status FROM registrations WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response = array(
                "success" => false,
                "message" => "No account found with this email address."
            );
            echo json_encode($response);
            exit;
        }

        $user = $result->fetch_assoc();
        error_log("Found user - ID: " . $user['id'] . ", Status: " . $user['status']);
        
        // Check if user is in a state that allows resend
        // Allow resend for: unverified, pending, and any status that's not 'approved' or 'rejected'
        if ($user['status'] === 'approved') {
            error_log("Resend blocked - User is already approved");
            $response = array(
                "success" => false,
                "message" => "Your account is already approved. You can log in normally."
            );
            echo json_encode($response);
            exit;
        } else if ($user['status'] === 'rejected') {
            error_log("Resend blocked - User account was rejected");
            $response = array(
                "success" => false,
                "message" => "Your account was rejected. Please contact support or register with a different email."
            );
            echo json_encode($response);
            exit;
        }
        
        // Allow resend for any other status (unverified, pending, etc.)
        error_log("Resend allowed - User status: " . $user['status']);

        // Generate new verification code
        $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
        $expiryTime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        error_log("Generated new code: " . $verificationCode . " for user: " . $email);

        // Update or insert verification record
        $updateStmt = $conn->prepare("
            INSERT INTO email_verifications (user_id, email, verification_code, expiry_time, created_at) 
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            verification_code = VALUES(verification_code),
            expiry_time = VALUES(expiry_time),
            created_at = NOW()
        ");
        $updateStmt->bind_param("isss", $user['id'], $email, $verificationCode, $expiryTime);
        
        if ($updateStmt->execute()) {
            // Send verification email
            error_log("Sending resend verification email to: " . $email);
            $emailSent = sendVerificationEmail($email, $user['first_name'], $verificationCode);
            
            if ($emailSent) {
                $response = array(
                    "success" => true,
                    "message" => "Verification code resent successfully! Please check your email."
                );
                error_log("Resend email sent successfully");
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Failed to send verification email. Please try again."
                );
                error_log("Resend email failed to send");
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Failed to update verification record."
            );
            error_log("Failed to update verification record");
        }
        
        $updateStmt->close();
        
    } else if ($action === "verify_code") {
        error_log("Verify code request for email: " . $email);
        
        $verificationCode = $_POST['verificationCode'] ?? null;
        
        if (!$verificationCode) {
            $response = array(
                "success" => false,
                "message" => "Verification code is required."
            );
            echo json_encode($response);
            exit;
        }
        
        // Check if verification code is valid and not expired
        error_log("Verifying code: " . $verificationCode . " for email: " . $email);
        
        $stmt = $conn->prepare("
            SELECT ev.id, ev.user_id, ev.verification_code, ev.expiry_time, r.first_name, r.status 
            FROM email_verifications ev 
            JOIN registrations r ON ev.user_id = r.id 
            WHERE ev.email = ? AND ev.verification_code = ?
        ");
        $stmt->bind_param("ss", $email, $verificationCode);
        $stmt->execute();
        $result = $stmt->get_result();

        error_log("Verification query result: " . $result->num_rows . " rows found");

        if ($result->num_rows === 0) {
            // Check if code exists but is expired
            $expiredStmt = $conn->prepare("
                SELECT ev.expiry_time, ev.verification_code 
                FROM email_verifications ev 
                WHERE ev.email = ? AND ev.verification_code = ?
            ");
            $expiredStmt->bind_param("ss", $email, $verificationCode);
            $expiredStmt->execute();
            $expiredResult = $expiredStmt->get_result();
            
            if ($expiredResult->num_rows > 0) {
                $expiredData = $expiredResult->fetch_assoc();
                error_log("Code found but expired. Expiry time: " . $expiredData['expiry_time'] . ", Current time: " . date('Y-m-d H:i:s'));
                $response = array(
                    "success" => false,
                    "message" => "Verification code has expired. Please request a new code."
                );
            } else {
                error_log("No verification code found for email: " . $email);
                $response = array(
                    "success" => false,
                    "message" => "Invalid verification code. Please check the code and try again."
                );
            }
            echo json_encode($response);
            exit;
        }

        // Check if code is expired
        $verification = $result->fetch_assoc();
        error_log("Found verification record - ID: " . $verification['id'] . ", Expiry: " . $verification['expiry_time']);
        
        if (strtotime($verification['expiry_time']) < time()) {
            error_log("Code is expired. Expiry: " . $verification['expiry_time'] . ", Current: " . date('Y-m-d H:i:s'));
            $response = array(
                "success" => false,
                "message" => "Verification code has expired. Please request a new code."
            );
            echo json_encode($response);
            exit;
        }

        // Update user status to pending and mark email as verified
        $updateStmt = $conn->prepare("UPDATE registrations SET status = 'pending', email_verified = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $verification['user_id']);
        
        if ($updateStmt->execute()) {
            // Delete the verification record since it's been used
            $deleteStmt = $conn->prepare("DELETE FROM email_verifications WHERE id = ?");
            $deleteStmt->bind_param("i", $verification['id']);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            $response = array(
                "success" => true,
                "message" => "Email verified successfully! Your account is now pending admin approval."
            );
            error_log("Email verification successful for user: " . $email);
        } else {
            $response = array(
                "success" => false,
                "message" => "Failed to update account status. Please try again."
            );
            error_log("Failed to update account status for user: " . $email);
        }
        
        $updateStmt->close();
        
    } else {
        $response = array(
            "success" => false,
            "message" => "Invalid action specified."
        );
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    $response = array(
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    );
    echo json_encode($response);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Send verification email with code
 */
function sendVerificationEmail($email, $firstName, $verificationCode) {
    $subject = "BoardEase - Email Verification Code";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 0; }
            .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .verification-code { 
                background-color: #f8f9fa; 
                border: 2px solid #2196F3; 
                font-size: 24px; 
                font-weight: bold; 
                color: #2196F3; 
                padding: 15px; 
                text-align: center; 
                margin: 20px 0;
                border-radius: 5px;
                letter-spacing: 3px;
            }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .warning { background-color: #ffeb3b; padding: 10px; border-left: 4px solid #ff9800; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Email Verification</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($firstName) . "!</h2>
                <p>Thank you for registering with BoardEase. To complete your registration, please verify your email address using the code below:</p>
                
                <div class='verification-code'>" . $verificationCode . "</div>
                
                <div class='warning'>
                    <strong>Important:</strong> This verification code will expire in 30 minutes. If you don't verify your email within this time, your account will be automatically deleted.
                </div>
                
                <p>If you didn't create an account with BoardEase, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from BoardEase. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Use the configured email system (Gmail SMTP)
    return sendEmail($email, $subject, $message);
}
?>
