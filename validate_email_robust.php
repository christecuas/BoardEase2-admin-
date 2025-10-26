<?php
// validate_email_robust.php - Robust email validation with better error handling

// Start output buffering to catch any errors
ob_start();

// Include email configuration
require_once 'email_config.php';

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Log the request for debugging
error_log("Email validation request received at " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));

try {
    // Database connection
    $servername = "localhost";
    $username   = "boardease";
    $password   = "boardease";
    $dbname     = "boardease2";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("DB Connection failed: " . $conn->connect_error);
    }

    $email = $_POST['email'] ?? null;

    if (!$email) {
        $response = array(
            "success" => false,
            "message" => "Email address is required."
        );
        echo json_encode($response);
        exit;
    }

    // Sanitize email
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = array(
            "success" => false,
            "message" => "Please enter a valid email address."
        );
        echo json_encode($response);
        exit;
    }

    // Check if email already exists
    error_log("=== EMAIL VALIDATION DEBUG ===");
    error_log("Checking email: " . $email);
    
    $stmt = $conn->prepare("SELECT id, status FROM registrations WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("Database query result: " . $result->num_rows . " rows found");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        error_log("Found existing user - ID: " . $user['id'] . ", Status: " . $user['status']);
        
        if ($user['status'] === 'approved') {
            $response = array(
                "success" => false,
                "message" => "This email is already registered and approved. Please use a different email or try logging in."
            );
        } else if ($user['status'] === 'pending') {
            $response = array(
                "success" => false,
                "message" => "This email is already registered and pending approval. Please use a different email or wait for approval."
            );
        } else if ($user['status'] === 'unverified') {
            $response = array(
                "success" => false,
                "message" => "This email is already registered but not verified. Please check your email for verification code or use a different email."
            );
        } else if ($user['status'] === 'rejected') {
            $response = array(
                "success" => false,
                "message" => "This email was previously rejected. Please use a different email address."
            );
        }
        
        echo json_encode($response);
        exit;
    }

    // For new emails, return success immediately without sending email
    // The email will be sent during the actual registration process
    $response = array(
        "success" => true,
        "message" => "Email address is valid and available for registration.",
        "email_verified" => true
    );

    error_log("Email validation successful for: " . $email . " (FAST MODE - NO EMAIL SENT)");
    error_log("=== EMAIL VALIDATION COMPLETE ===");

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Email validation error: " . $e->getMessage());
    $response = array(
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    );
    echo json_encode($response);
}

$conn->close();
?>
