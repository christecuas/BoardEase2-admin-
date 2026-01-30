<?php
// forgot_password.php
// Handles forgot password requests - generates token and sends email

// Disable output buffering and ensure clean output
ob_start();

// Handle preflight OPTIONS request (for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    ob_end_flush();
    exit;
}

// Set headers first, before any output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept, Authorization');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Clear any previous output
ob_clean();

// Suppress error display (but log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
    $response = array(
        "success" => false,
        "message" => "Database connection failed"
    );
    echo json_encode($response);
    exit;
}

// Get POST data
$email = $_POST['email'] ?? null;

// Validate input
if (!$email) {
    $response = array(
        "success" => false,
        "message" => "Email is required"
    );
    echo json_encode($response);
    exit;
}

// Sanitize email
$email = trim($email);
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = array(
        "success" => false,
        "message" => "Invalid email format"
    );
    echo json_encode($response);
    exit;
}

// Check if email exists in registrations table
$stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM registrations WHERE email = ?");
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
    // Email does not exist
    $response = array(
        "success" => false,
        "emailExists" => false,
        "message" => "No account found with this email address. Please check your email and try again."
    );
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Generate unique token
$token = bin2hex(random_bytes(32)); // 64 character token

// Set expiration time (30 minutes from now)
$expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// Delete any existing tokens for this email
$stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->close();

// Insert new token
$stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
if (!$stmt) {
    $response = array(
        "success" => false,
        "message" => "Database error: " . $conn->error
    );
    echo json_encode($response);
    $conn->close();
    exit;
}

$stmt->bind_param("sss", $email, $token, $expires_at);
$insertResult = $stmt->execute();
$stmt->close();

if (!$insertResult) {
    $response = array(
        "success" => false,
        "message" => "Failed to create reset token"
    );
    echo json_encode($response);
    $conn->close();
    exit;
}

// Return response immediately to prevent timeout
// Send email in background after response is sent
$response = array(
    "success" => true,
    "emailExists" => true,
    "message" => "Password reset link has been sent to your email. Please check your inbox and click the reset button."
);

$conn->close();

// Prepare email data before sending response (so we can send response immediately)
$userName = $user['first_name'] . " " . $user['last_name'];
$subject = "Password Reset Request - BoardEase";

// Build reset link - use simple HTTPS link (most reliable and clickable in email clients)
// The redirect page will immediately open the app
$resetLink = "https://boardease.calapebohol.com/redirect_reset_password.php?token=" . urlencode($token);

// Build email message
$message = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #A18167; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .button { 
            display: inline-block; 
            padding: 14px 40px; 
            background-color: #A18167; 
            color: white !important; 
            text-decoration: none !important; 
            border-radius: 5px; 
            margin: 20px 0; 
            font-size: 16px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background-color: #8a6f56;
        }
        .footer { padding: 20px; text-align: center; color: #777; font-size: 12px; }
        .warning { color: #d32f2f; font-weight: bold; }
        .verification-link { 
            margin: 20px 0; 
            padding: 15px; 
            background-color: #f8f9fa; 
            border-left: 4px solid #007bff; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>BoardEase Password Reset</h2>
        </div>
        <div class='content'>
            <p>Hello " . htmlspecialchars($userName) . ",</p>
            <p>We received a request to reset your password for your BoardEase account.</p>
            <div class='verification-link'>
                <p><strong>Quick Access:</strong> Click the button below to open the password reset screen directly in the BoardEase app:</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='" . htmlspecialchars($resetLink, ENT_QUOTES) . "' style='display: inline-block; padding: 14px 40px; background-color: #A18167; color: #FFFFFF !important; text-decoration: none !important; border-radius: 5px; font-size: 16px; font-weight: bold; border: 2px solid #A18167;'>Reset Password</a>
                </div>
                <p style='font-size: 12px; color: #666; margin-top: 10px;'><strong>Note:</strong> Clicking the button will open a page that will immediately try to open the BoardEase app. If Android shows an app chooser, please select <strong>BoardEase</strong> and choose <strong>&quot;Always&quot;</strong> to set it as default.</p>
            </div>
            <p class='warning'>⚠️ This link will expire in 30 minutes.</p>
            <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
        </div>
        <div class='footer'>
            <p>This is an automated message from BoardEase. Please do not reply to this email.</p>
            <p>&copy; " . date('Y') . " BoardEase. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
";

// Send response first (before email to prevent timeout)
// Clean any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Send JSON response
echo json_encode($response);

// Flush output immediately
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // Finish request for FastCGI - client gets response now
} else {
    // For non-FastCGI, flush all output
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

// Now send email in background (after response is sent to client)
// Suppress any output from email_config.php
ob_start();
require_once 'email_config.php';
$output = ob_get_clean(); // Capture and discard any output

// Send email - wrap in try-catch to prevent errors
try {
    if (function_exists('sendEmail')) {
        $emailSent = @sendEmail($email, $subject, $message);
        if ($emailSent) {
            error_log("Password reset email sent successfully to: " . $email);
        } else {
            error_log("Failed to send password reset email to: " . $email . " (but token was saved)");
        }
    } else {
        error_log("sendEmail function not found in email_config.php");
    }
} catch (Exception $e) {
    error_log("Email sending exception: " . $e->getMessage());
} catch (Error $e) {
    error_log("Email sending error: " . $e->getMessage());
}
exit;
?>

