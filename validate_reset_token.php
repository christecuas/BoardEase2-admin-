<?php
// validate_reset_token.php
// Validates reset token before allowing password reset

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('ngrok-skip-browser-warning: true');

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
        "valid" => false,
        "message" => "Database connection failed"
    );
    echo json_encode($response);
    exit;
}

// Get token from POST or GET
$token = $_POST['token'] ?? $_GET['token'] ?? null;

if (!$token) {
    $response = array(
        "success" => false,
        "valid" => false,
        "message" => "Token is required"
    );
    echo json_encode($response);
    $conn->close();
    exit;
}

// Validate token
$stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
if (!$stmt) {
    $response = array(
        "success" => false,
        "valid" => false,
        "message" => "Database error: " . $conn->error
    );
    echo json_encode($response);
    $conn->close();
    exit;
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = array(
        "success" => true,
        "valid" => false,
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
        "success" => true,
        "valid" => false,
        "message" => "This reset link has already been used. Please request a new one."
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
        "success" => true,
        "valid" => false,
        "message" => "This reset link has expired. Please request a new one."
    );
    echo json_encode($response);
    $conn->close();
    exit;
}

// Token is valid
$response = array(
    "success" => true,
    "valid" => true,
    "message" => "Token is valid"
);

$conn->close();
echo json_encode($response);
?>

