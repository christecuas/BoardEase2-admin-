<?php
// insert_registration_simplified.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Helper to get POST data
function getPost($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

$role = getPost('role');
$firstName = getPost('firstName');
$middleName = getPost('middleName');
$lastName = getPost('lastName');
$suffix = getPost('suffix');
if ($suffix === 'None' || $suffix === 'none') {
    $suffix = null;
}
$birthDate = getPost('birthDate');
$phone = getPost('phone');
$email = getPost('email');
$password = getPost('password');
$isGoogle = getPost('is_google'); // '1' for Google, '0' or null for manual

// Debug log to see all parameters received
error_log("Registration details received: " . print_r($_POST, true));
error_log("Detected isGoogle value: " . ($isGoogle ?? 'NULL'));

// Basic Validation
if (!$role || !$firstName || !$lastName || !$email || (!$password && $isGoogle != '1')) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

// Date Formatting
if ($birthDate) {
    $dateObj = DateTime::createFromFormat('m/d/Y', $birthDate);
    if (!$dateObj) $dateObj = DateTime::createFromFormat('Y-m-d', $birthDate);
    
    if ($dateObj) {
        $birthDate = $dateObj->format('Y-m-d');
    } else {
        echo json_encode(["success" => false, "message" => "Invalid birth date format."]);
        exit();
    }
}

// Hash Password (only for manual)
$hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

// Determine Initial Status
// Google sign-up starts at 'profile_incomplete' (email is verified the Google)
// Manual registration starts at 'email_unverified'
$initialStatus = ($isGoogle == '1') ? 'profile_incomplete' : 'email_unverified';
$emailVerified = ($isGoogle == '1') ? 1 : 0;

// Insert Query
$sql = "INSERT INTO registrations 
        (role, first_name, middle_name, last_name, suffix, birth_date, phone, email, password, status, email_verified, cb_agreed, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1', NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "SQL Prepare Error: " . $conn->error]);
    exit();
}

// Bind Parameters: ssssssssssi (10 strings, 1 integer)
$stmt->bind_param("ssssssssssi", 
    $role, 
    $firstName, 
    $middleName, 
    $lastName, 
    $suffix, 
    $birthDate, 
    $phone, 
    $email, 
    $hashedPassword,
    $initialStatus,
    $emailVerified
);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    
    // Handle Email Notification
    if (file_exists('email_config.php')) {
        require_once 'email_config.php';
        
        if ($isGoogle == '1') {
            // Send "Update Profile" Invitation for Google Users
            $subject = "Welcome to BoardEase! Complete your profile";
            $emailBody = "
                <h2>Welcome $firstName!</h2>
                <p>Thank you for signing up for BoardEase via Google.</p>
                <p>To fully verify your account and access all features (like booking or posting properties), please log in and complete your profile by uploading your ID and required permits.</p>
                <p>You can explore the app and browse properties right now!</p>
                <div style='text-align: center;'>
                    <a href='https://boardease.calapebohol.com/open_app.php' class='btn' style='background-color: #6d4c41; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Complete Profile Now</a>
                </div>
            ";
            $message = getProfessionalEmailTemplate($subject, $emailBody);
            sendEmail($email, $subject, $message);
            
            echo json_encode([
                "success" => true, 
                "message" => "Registration successful! Welcome to BoardEase.",
                "user_id" => $userId,
                "status" => $initialStatus
            ]);
        } else {
            // Handle Manual Registration - Send OTP
            $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
            $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $verifySql = "INSERT INTO email_verifications (user_id, email, verification_code, expiry_time, created_at) VALUES (?, ?, ?, ?, NOW())";
            $verifyStmt = $conn->prepare($verifySql);
            if ($verifyStmt) {
                $verifyStmt->bind_param("isss", $userId, $email, $verificationCode, $expiryTime);
                $verifyStmt->execute();
                $verifyStmt->close();
                
                // Use the new professional template
                $subject = "BoardEase - Your Verification Code";
                $emailBody = "
                    <h2>Verify Your Email</h2>
                    <p>Hello $firstName,</p>
                    <p>Thank you for registering. Please use the verification code below to complete your sign-up process:</p>
                    <div class='otp-box'>$verificationCode</div>
                    <p>This code will expire in 1 hour for your security. If you didn't request this, you can safely ignore this email.</p>
                ";
                $message = getProfessionalEmailTemplate($subject, $emailBody);
                sendEmail($email, $subject, $message);
            }

            echo json_encode([
                "success" => true, 
                "message" => "Registration successful! Please check your email for the verification code.",
                "user_id" => $userId,
                "status" => $initialStatus,
                "needs_verification" => true
            ]);
        }
    } else {
        echo json_encode([
            "success" => true, 
            "message" => "Registration successful! (Email system offline)",
            "user_id" => $userId,
            "status" => $initialStatus
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Registration failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
