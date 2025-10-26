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

$bhr_id = $_POST['bhr_id'] ?? null;
$image_base64 = $_POST['image_base64'] ?? null;

if (!$bhr_id || !$image_base64) {
    echo json_encode(["success" => false, "error" => "Required parameters missing"]);
    exit;
}

// Decode base64 image
$image_data = base64_decode($image_base64);
if ($image_data === false) {
    echo json_encode(["success" => false, "error" => "Invalid image data"]);
    exit;
}

// Generate unique filename
$filename = 'room_' . $bhr_id . '_' . uniqid() . '.jpg';
$upload_dir = 'uploads/room_images/';
$file_path = $upload_dir . $filename;

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Save image file
if (file_put_contents($file_path, $image_data)) {
    // Insert into database
    $sql = "INSERT INTO room_images (bhr_id, image_path) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $bhr_id, $file_path);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "image_path" => $file_path]);
    } else {
        unlink($file_path); // Delete file if database insert fails
        echo json_encode(["success" => false, "error" => "Failed to save image record"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Failed to save image file"]);
}

$conn->close();
?>