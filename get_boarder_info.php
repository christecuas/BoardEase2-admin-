<?php
// get_boarder_info.php
// Fetches boarder profile information from the database

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
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
    $errorMsg = "Database connection failed: " . $conn->connect_error;
    error_log("get_boarder_info failed: " . $errorMsg);
    $response = array(
        "success" => false,
        "error" => $errorMsg
    );
    echo json_encode($response);
    exit;
}

// Get parameters
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : null);
$email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : null);

// Validate input
if (!$user_id && !$email) {
    $response = array(
        "success" => false,
        "error" => "User ID or Email is required"
    );
    echo json_encode($response);
    exit;
}

$stmt = null;

if ($email) {
    // Priority 1: Lookup by Email (Most robust for "Soft" users)
    // Direct lookup in registrations to avoid missing 'users' entry issues
    $stmt = $conn->prepare("SELECT r.id, r.first_name, r.middle_name, r.last_name, r.suffix, 
                                  r.email, r.phone, r.birth_date, r.address
                           FROM registrations r
                           WHERE r.email = ?");
    $stmt->bind_param("s", $email);
} else {
    // Priority 2: Lookup by ID
    // We try to match either users.user_id OR registrations.id
    // This handles both fully approved users (user_id) and new 'soft' users (reg_id)
    $user_id = intval($user_id);
    $stmt = $conn->prepare("SELECT r.id, r.first_name, r.middle_name, r.last_name, r.suffix, 
                                  r.email, r.phone, r.birth_date, r.address
                           FROM registrations r
                           LEFT JOIN users u ON r.id = u.reg_id
                           WHERE u.user_id = ? OR r.id = ?");
    $stmt->bind_param("ii", $user_id, $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = array(
        "success" => false,
        "error" => "Boarder not found"
    );
    echo json_encode($response);
} else {
    $boarder = $result->fetch_assoc();
    
    // Format birthdate if it exists
    $birthdate = null;
    if ($boarder['birth_date']) {
        $birthdate = $boarder['birth_date'];
    }
    
    // Handle suffix - convert null/empty/null string to "none", or normalize "none" variants
    $suffix = $boarder['suffix'];
    if ($suffix === null || $suffix === '' || strtolower(trim($suffix)) === 'null') {
        $suffix = 'none';
    } else {
        // Normalize any "none" variant to lowercase "none"
        $suffixLower = strtolower(trim($suffix));
        if ($suffixLower === 'none') {
            $suffix = 'none';
        }
    }
    
    // Build response with all required fields
    $response = array(
        "success" => true,
        "data" => array(
            "first_name" => $boarder['first_name'] ? $boarder['first_name'] : "",
            "middle_name" => $boarder['middle_name'] ? $boarder['middle_name'] : "",
            "last_name" => $boarder['last_name'] ? $boarder['last_name'] : "",
            "suffix" => $suffix,
            "email" => $boarder['email'] ? $boarder['email'] : "",
            "contact" => $boarder['phone'] ? $boarder['phone'] : "",
            "birthdate" => $birthdate ? $birthdate : "",
            "address" => $boarder['address'] ? $boarder['address'] : ""
        )
    );
    
    echo json_encode($response);
}

$stmt->close();
$conn->close();
?>
