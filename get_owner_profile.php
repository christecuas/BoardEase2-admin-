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

// Get user_id from POST request
$user_id = $_POST["user_id"] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "error" => "User ID is required"]);
    exit;
}

try {
    // Get email from POST (optional)
    $email = $_POST["email"] ?? null;

    if ($email) {
        // Priority 1: Email lookup (Robust for incomplete profiles)
        $sql = "SELECT r.first_name, r.middle_name, r.last_name, r.suffix, r.birth_date, r.phone, r.address, r.email, u.profile_picture, r.role, r.status
                FROM registrations r
                LEFT JOIN users u ON r.id = u.reg_id
                WHERE r.email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
    } else {
        // Priority 2: User ID lookup (Robust LEFT JOIN)
        $sql = "SELECT r.first_name, r.middle_name, r.last_name, r.suffix, r.birth_date, r.phone, r.address, r.email, u.profile_picture, r.role, r.status
                FROM registrations r
                LEFT JOIN users u ON r.id = u.reg_id
                WHERE u.user_id = ? OR r.id = ?"; // Check both IDs
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Handle suffix: if NULL in database, return "None" for display
        $suffix = $row["suffix"];
        if ($suffix === null || $suffix === '') {
            $suffix = "None";
        }
        
        echo json_encode([
            "success" => true,
            "f_name" => $row["first_name"] ?? "",
            "m_name" => $row["middle_name"] ?? "",
            "l_name" => $row["last_name"] ?? "",
            "suffix" => $suffix,
            "birthdate" => $row["birth_date"] ?? "",
            "phone_number" => $row["phone"] ?? "",
            "p_address" => $row["address"] ?? "",
            "email" => $row["email"] ?? "",
            "profile_picture" => $row["profile_picture"] ?? "",
            "status" => $row["status"] ?? "profile_incomplete"
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Owner profile not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>














