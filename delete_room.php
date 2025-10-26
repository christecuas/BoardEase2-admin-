<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$servername = "localhost";
$username = "boardease";
$password = "boardease";
$database = "boardease2";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Connection failed: " . $conn->connect_error]));
}

$room_id = $_POST['room_id'] ?? null;

if (!$room_id) {
    echo json_encode(["success" => false, "error" => "Room ID is required"]);
    exit;
}

$conn->begin_transaction();

try {
    // Get image paths before deleting
    $image_sql = "SELECT image_path FROM room_images WHERE bhr_id = ?";
    $image_stmt = $conn->prepare($image_sql);
    $image_stmt->bind_param("i", $room_id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();
    
    $images_to_delete = [];
    while ($row = $image_result->fetch_assoc()) {
        $images_to_delete[] = $row['image_path'];
    }
    
    // Delete room images from database
    $delete_images_sql = "DELETE FROM room_images WHERE bhr_id = ?";
    $delete_images_stmt = $conn->prepare($delete_images_sql);
    $delete_images_stmt->bind_param("i", $room_id);
    $delete_images_stmt->execute();
    
    // Delete room from database
    $delete_room_sql = "DELETE FROM boarding_house_rooms WHERE bhr_id = ?";
    $delete_room_stmt = $conn->prepare($delete_room_sql);
    $delete_room_stmt->bind_param("i", $room_id);
    $delete_room_stmt->execute();
    
    // Delete image files
    foreach ($images_to_delete as $image_path) {
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Room deleted successfully"]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Failed to delete room: " . $e->getMessage()]);
}

$conn->close();
?>