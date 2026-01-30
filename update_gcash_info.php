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

// Get parameters from POST request
$user_id = $_POST["user_id"] ?? null;
$gcash_number = $_POST["gcash_number"] ?? null;

// Validate required fields
if (!$user_id || !$gcash_number) {
    echo json_encode(["success" => false, "error" => "User ID and GCash number are required"]);
    exit;
}

try {
    // Update GCash number
    $sql = "UPDATE registrations r 
            JOIN users u ON r.id = u.reg_id 
            SET r.gcash_num = ? 
            WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $gcash_number, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "GCash information updated successfully"
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update GCash information"]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
} finally {
    $conn->close();
}
?>



























