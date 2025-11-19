<?php
// update_password.php
// Updates password after token validation

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "boardease";
$password = "boardease";
$dbname = "boardease2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $response = array(
        "success" => false,
        "message" => "Database connection failed"
    );
    echo json_encode($response);
    exit;
}

// Get POST data
$token = $_POST['token'] ?? null;
$newPassword = $_POST['password'] ?? null;
$confirmPassword = $_POST['confirm_password'] ?? null;

// Validate input
if (!$token || !$newPassword || !$confirmPassword) {
    $response = array(
        "success" => false,
        "message" => "All fields are required"
    );
    echo json_encode($response);
    exit;
}

// Validate password length
if (strlen($newPassword) < 8) {
    $response = array(
        "success" => false,
        "message" => "Password must be at least 8 characters long"
    );
    echo json_encode($response);
    exit;
}

// Check if passwords match
if ($newPassword !== $confirmPassword) {
    $response = array(
        "success" => false,
        "message" => "Passwords do not match"
    );
    echo json_encode($response);
    exit;
}

// Validate token
$stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
if (!$stmt) {
    $response = array(
        "success" => false,
        "message" => "Database error: " . $conn->error
    );
    echo json_encode($response);
    exit;
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = array(
        "success" => false,
        "message" => "Invalid or expired reset token"
    );
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit;
}

$resetData = $result->fetch_assoc();
$stmt->close();

// Check if token is used
if ($resetData['used'] == 1) {
    $response = array(
        "success" => false,
        "message" => "This reset link has already been used"
    );
    echo json_encode($response);
    $conn->close();
    exit;
}

// Check if token is expired
$expiresAt = strtotime($resetData['expires_at']);
$now = time();

if ($now > $expiresAt) {
    $response = array(
        "success" => false,
        "message" => "This reset link has expired. Please request a new one."
    );
    echo json_encode($response);
    $conn->close();
    exit;
}

$email = $resetData['email'];

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Update password in registrations table
    $stmt = $conn->prepare("UPDATE registrations SET password = ? WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $hashedPassword, $email);
    $updateResult = $stmt->execute();
    $stmt->close();
    
    if (!$updateResult) {
        throw new Exception("Failed to update password");
    }
    
    // Mark token as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    $response = array(
        "success" => true,
        "message" => "Password has been reset successfully"
    );
    
    error_log("Password reset successful for email: " . $email);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $response = array(
        "success" => false,
        "message" => $e->getMessage()
    );
    
    error_log("Password reset error: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>

