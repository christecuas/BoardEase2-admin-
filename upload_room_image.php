<?php
// upload_room_image.php
header('Content-Type: application/json');
include 'dbConfig.php'; // $conn = mysqli connection

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

try {
    $bhr_id = isset($_POST['bhr_id']) ? intval($_POST['bhr_id']) : 0;
    $image_base64 = isset($_POST['image_base64']) ? $_POST['image_base64'] : '';

    if ($bhr_id <= 0 || empty($image_base64)) {
        echo json_encode(['error' => 'Missing parameters.']);
        exit;
    }

    // Decode base64
    $image_data = base64_decode($image_base64);
    if ($image_data === false) {
        echo json_encode(['error' => 'Invalid image data.']);
        exit;
    }

    // Upload directory
    $upload_dir = __DIR__ . '/uploads/room_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $filename = 'bhr_' . $bhr_id . '_' . uniqid() . '.jpg';
    $file_path = $upload_dir . $filename;

    // Save image to server
    if (file_put_contents($file_path, $image_data) === false) {
        echo json_encode(['error' => 'Failed to save image on server.']);
        exit;
    }

    // Save only relative path in DB
    $relative_path = 'uploads/room_images/' . $filename;

    $stmt = $conn->prepare("INSERT INTO room_images (bhr_id, image_path) VALUES (?, ?)");
    $stmt->bind_param("is", $bhr_id, $relative_path);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'image_path' => $relative_path,
        'message' => 'Room image uploaded successfully.'
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
