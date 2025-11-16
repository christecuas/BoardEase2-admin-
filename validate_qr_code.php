<?php
// QR Code Validation Endpoint for Android App
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS for Android app
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['isValid' => false, 'reason' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
error_log("QR Validation: Raw input - " . substr($input, 0, 200) . "...");

$data = json_decode($input, true);

if (!$data || !isset($data['image_data'])) {
    error_log("QR Validation: Missing image_data parameter");
    http_response_code(400);
    echo json_encode(['isValid' => false, 'reason' => 'Missing image_data parameter']);
    exit();
}

$base64Image = $data['image_data'];
error_log("QR Validation: Received base64 image data, length: " . strlen($base64Image));

// Validate base64 image
if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
    // If no data URI prefix, assume it's raw base64
    if (!base64_decode($base64Image, true)) {
        error_log("QR Validation: Invalid base64 image data");
        http_response_code(400);
        echo json_encode(['isValid' => false, 'reason' => 'Invalid base64 image data']);
        exit();
    }
    $imageData = base64_decode($base64Image);
} else {
    $imageData = base64_decode(substr($base64Image, strpos($base64Image, ',') + 1));
}

if (!$imageData) {
    error_log("QR Validation: Failed to decode base64 image");
    http_response_code(400);
    echo json_encode(['isValid' => false, 'reason' => 'Failed to decode base64 image']);
    exit();
}

// Save temporary image file
$tempDir = sys_get_temp_dir();
$tempFile = tempnam($tempDir, 'qr_validation_') . '.jpg';

if (file_put_contents($tempFile, $imageData) === false) {
    error_log("QR Validation: Failed to save temporary image");
    http_response_code(500);
    echo json_encode(['isValid' => false, 'reason' => 'Failed to save temporary image']);
    exit();
}

error_log("QR Validation: Processing image - " . $tempFile);
error_log("QR Validation: Image size - " . filesize($tempFile) . " bytes");

try {
    // Include the validation functions from insert_registration.php
    // But prevent it from executing the main registration logic
    $originalPost = $_POST;
    $_POST = array(); // Clear POST data to prevent registration logic
    
    require_once 'insert_registration.php';
    
    // Restore original POST data
    $_POST = $originalPost;
    
    // Validate the QR code using our strict server-side validation
    $validationResult = validateGcashQrCode($tempFile);
    
    error_log("QR Validation: Result - " . json_encode($validationResult));
    
    // Clean up temporary file
    unlink($tempFile);
    
    // Return the validation result in the expected format
    echo json_encode([
        'isValid' => $validationResult['isValid'],
        'reason' => $validationResult['reason'] ?? 'Unknown reason'
    ]);
    
} catch (Exception $e) {
    error_log("QR Validation: Exception - " . $e->getMessage());
    
    // Clean up temporary file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    http_response_code(500);
    echo json_encode([
        'isValid' => false,
        'reason' => 'Server error during QR validation: ' . $e->getMessage()
    ]);
}
?>
