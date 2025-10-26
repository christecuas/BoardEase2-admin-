<?php
// upload_bh_image.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0); // suppress warnings/notices
ini_set('display_errors', 0);

include 'dbConfig.php'; // $conn should be defined here (mysqli)

try {
    // Get POST parameters
    $bh_id = isset($_POST['bh_id']) ? intval($_POST['bh_id']) : 0;
    $image_base64 = isset($_POST['image_base64']) ? $_POST['image_base64'] : '';

    if ($bh_id <= 0 || empty($image_base64)) {
        echo json_encode(["error" => "Missing parameters."]);
        exit;
    }

    // Decode base64 safely
    $image_data = base64_decode($image_base64, true);
    if ($image_data === false) {
        echo json_encode(["error" => "Invalid image data."]);
        exit;
    }

    // Ensure uploads directory exists
    $upload_dir = __DIR__ . '/uploads/boarding_house_images/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        echo json_encode(["error" => "Failed to create upload directory."]);
        exit;
    }

    // Generate a unique filename
    $filename = 'bh_' . $bh_id . '_' . uniqid() . '.jpg';
    $file_path = $upload_dir . $filename;

    // Save image to server
    if (file_put_contents($file_path, $image_data) === false) {
        echo json_encode(["error" => "Failed to save image."]);
        exit;
    }

    // Use relative path for DB
    $db_path = 'uploads/boarding_house_images/' . $filename;

    // Insert into database using mysqli
    $stmt = $conn->prepare("INSERT INTO boarding_house_images (bh_id, image_path) VALUES (?, ?)");
    if (!$stmt) {
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("is", $bh_id, $db_path);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "image_path" => $db_path]);
    } else {
        echo json_encode(["error" => "Execute failed: " . $stmt->error]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(["error" => "Unexpected error: " . $e->getMessage()]);
}
?>
