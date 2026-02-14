<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Connection failed: " . $conn->connect_error]));
}

// Get params
$user_id = $_POST["user_id"] ?? null;
$email = $_POST["email"] ?? null;

if (!$user_id && !$email) {
    echo json_encode(["success" => false, "error" => "User ID or Email is required"]);
    exit;
}

try {
    $sql = "";
    $stmt = null;

    if ($email) {
        // Priority 1: Email lookup
        $sql = "SELECT r.email, r.gcash_num, r.gcash_qr 
                FROM registrations r 
                WHERE r.email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
    } else {
        // Priority 2: ID lookup (Loose)
        $sql = "SELECT r.email, r.gcash_num, r.gcash_qr 
                FROM registrations r 
                LEFT JOIN users u ON r.id = u.reg_id 
                WHERE u.user_id = ? OR r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        echo json_encode([
            "success" => true,
            "email" => $row["email"],
            "gcash_number" => $row["gcash_num"],
            "gcash_qr" => $row["gcash_qr"]
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "User not found"]);
    }
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
} finally {
    $conn->close();
}
?>



























