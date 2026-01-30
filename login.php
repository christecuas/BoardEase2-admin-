<?php
// login.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request for debugging
error_log("Login request received at " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));

// Set content type to JSON
header('Content-Type: application/json');

// Database connection
require_once 'dbConfig.php';

// Check connection
if ($conn->connect_error) {
    $errorMsg = "Database connection failed: " . $conn->connect_error;
    error_log("Login failed: " . $errorMsg);
    $response = array(
        "success" => false,
        "message" => $errorMsg
    );
    echo json_encode($response);
    exit;
}

// Get POST data
$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

// Validate input
if (!$email || !$password) {
    $response = array(
        "success" => false,
        "message" => "Email and password are required"
    );
    echo json_encode($response);
    exit;
}

// Sanitize input
$email = trim($email);
$password = trim($password);

// Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare("SELECT r.id, r.role, r.first_name, r.middle_name, r.last_name, r.suffix, r.email, r.password, r.status, u.user_id 
                        FROM registrations r 
                        LEFT JOIN users u ON r.id = u.reg_id 
                        WHERE r.email = ?");
if (!$stmt) {
    $response = array(
        "success" => false,
        "message" => "Database error: " . $conn->error
    );
    echo json_encode($response);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = array(
        "success" => false,
        "message" => "Invalid email or password"
    );
    echo json_encode($response);
} else {
    $user = $result->fetch_assoc();
    
    // Verify password
    // Check if password is hashed (starts with $2y$) or plain text
    if (strpos($user['password'], '$2y$') === 0) {
        // Password is hashed, use password_verify
        $passwordValid = password_verify($password, $user['password']);
    } else {
        // Password is plain text (for backward compatibility)
        $passwordValid = ($password === $user['password']);
    }
    
    if ($passwordValid) {
        // Check if account is approved by admin
        if ($user['status'] === 'approved') {
            // If user exists in users table, check if account is suspended
            if ($user['user_id']) {
                $checkUserStatus = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
                $checkUserStatus->bind_param("i", $user['user_id']);
                $checkUserStatus->execute();
                $userStatusResult = $checkUserStatus->get_result();
                
                if ($userStatusResult->num_rows > 0) {
                    $userStatusData = $userStatusResult->fetch_assoc();
                    if ($userStatusData['status'] === 'Inactive') {
                        $response = array(
                            "success" => false,
                            "message" => "Your account has been suspended. Please contact the administrator for more information."
                        );
                        error_log("Login blocked - account suspended for: " . $email);
                        echo json_encode($response);
                        $checkUserStatus->close();
                        $stmt->close();
                        $conn->close();
                        exit;
                    }
                }
                $checkUserStatus->close();
            }
            
            // Construct full name with suffix
            $fullName = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
            if (!empty($user['suffix'])) {
                $fullName .= ' ' . $user['suffix'];
            }
            
            // Note: Activity tracking is now handled by device_tokens heartbeat
            // We don't update users table anymore for this to avoid schema changes

            $response = array(
                "success" => true,
                "message" => "Login successful",
                "user" => array(
                    "id" => $user['user_id'] ? $user['user_id'] : $user['id'], // Use user_id if available, fallback to registration id
                    "role" => $user['role'],
                    "firstName" => $user['first_name'],
                    "middleName" => $user['middle_name'],
                    "lastName" => $user['last_name'],
                    "suffix" => $user['suffix'],
                    "fullName" => $fullName,
                    "email" => $user['email']
                )
            );
            error_log("Login successful for user: " . $email);
            error_log("Response being sent: " . json_encode($response));
            echo json_encode($response);
        } else if ($user['status'] === 'unverified') {
            $response = array(
                "success" => false,
                "message" => "Please verify your email address before logging in. Check your email for the verification code.",
                "requires_verification" => true
            );
            error_log("Login blocked - account unverified for: " . $email);
            echo json_encode($response);
        } else if ($user['status'] === 'pending') {
            $response = array(
                "success" => false,
                "message" => "Your account is still pending admin approval. Please wait for approval before logging in."
            );
            error_log("Login blocked - account pending approval for: " . $email);
            echo json_encode($response);
        } else if ($user['status'] === 'rejected') {
            $response = array(
                "success" => false,
                "message" => "Your account has been rejected. Please contact the administrator for more information."
            );
            error_log("Login blocked - account rejected for: " . $email);
            echo json_encode($response);
        } else {
            $response = array(
                "success" => false,
                "message" => "Your account status is invalid. Please contact the administrator."
            );
            error_log("Login blocked - invalid account status for: " . $email . " Status: " . $user['status']);
            echo json_encode($response);
        }
    } else {
        $response = array(
            "success" => false,
            "message" => "Invalid email or password"
        );
        error_log("Login failed - invalid credentials for: " . $email);
        echo json_encode($response);
    }
}

$stmt->close();
$conn->close();
?>
