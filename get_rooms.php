<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$servername = "localhost";
$username = "boardease";
$password = "boardease";
$database = "boardease2";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Connection failed: " . $conn->connect_error]));
}

// Get bh_id from POST request
$bh_id = $_POST["bh_id"] ?? null;

if (!$bh_id) {
    echo json_encode(["success" => false, "error" => "BH ID is required"]);
    exit;
}

try {
    // First, let us check if there are ANY rooms in the database
    $count_sql = "SELECT COUNT(*) as total FROM boarding_house_rooms";
    $count_result = $conn->query($count_sql);
    $total_rooms = $count_result->fetch_assoc()["total"];
    
    // Check rooms for this specific bh_id
    $count_bh_sql = "SELECT COUNT(*) as total FROM boarding_house_rooms WHERE bh_id = ?";
    $count_stmt = $conn->prepare($count_bh_sql);
    $count_stmt->bind_param("i", $bh_id);
    $count_stmt->execute();
    $bh_rooms = $count_stmt->get_result()->fetch_assoc()["total"];
    
    // Query to get rooms for the boarding house - using your actual column names
    $sql = "SELECT bhr_id, bh_id, room_category, room_name, price, capacity, room_description, total_rooms 
            FROM boarding_house_rooms 
            WHERE bh_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bh_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        // Get images for this room
        $image_sql = "SELECT image_path FROM room_images WHERE bhr_id = ?";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $row["bhr_id"]);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        
        $images = [];
        while ($image_row = $image_result->fetch_assoc()) {
            $images[] = $image_row["image_path"];
        }
        
        // Map your column names to the expected JSON structure
        $room_data = [
            "bhr_id" => $row["bhr_id"],
            "bh_id" => $row["bh_id"],
            "category" => $row["room_category"],  // room_category -> category
            "title" => $row["room_name"],         // room_name -> title
            "room_description" => $row["room_description"] ?? "",
            "price" => (float)$row["price"],
            "capacity" => (int)$row["capacity"],
            "total_rooms" => (int)$row["total_rooms"],
            "images" => $images
        ];
        
        $rooms[] = $room_data;
    }

    // Return debug info along with rooms
    echo json_encode([
        "success" => true, 
        "rooms" => $rooms,
        "debug" => [
            "total_rooms_in_db" => $total_rooms,
            "rooms_for_bh_id" => $bh_rooms,
            "bh_id_requested" => $bh_id,
            "rooms_returned" => count($rooms)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>