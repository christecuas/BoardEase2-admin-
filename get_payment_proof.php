<?php
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

// Start output buffering
ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');
header('Cache-Control: public, max-age=3600');

// Get image path from query parameter
$imagePath = isset($_GET['path']) ? $_GET['path'] : '';

if (empty($imagePath)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Missing path parameter']);
    ob_end_flush();
    exit;
}

// Security: Only allow paths that start with uploads/
// Prevent directory traversal attacks
$imagePath = str_replace('..', '', $imagePath);
// Remove any attempt to traverse directories
$imagePath = preg_replace('/\.\./', '', $imagePath);

// Allow paths starting with uploads/ (with or without leading slash)
if (strpos($imagePath, 'uploads/') !== 0 && strpos($imagePath, '/uploads/') !== 0) {
    ob_clean();
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid path. Path must start with uploads/']);
    ob_end_flush();
    exit;
}

// Remove leading slash if present
if (strpos($imagePath, '/') === 0) {
    $imagePath = substr($imagePath, 1);
}

// Construct full file path
// Files are stored in ../uploads/ relative to BoardEase2 directory
// So we need to go up one level from BoardEase2
$baseDir = dirname(__DIR__) . '/';
$fullPath = $baseDir . $imagePath;

error_log("get_payment_proof.php - Requested path: " . $imagePath);
error_log("get_payment_proof.php - Base directory: " . $baseDir);
error_log("get_payment_proof.php - Full path: " . $fullPath);
error_log("get_payment_proof.php - File exists: " . (file_exists($fullPath) ? 'YES' : 'NO'));

// If file doesn't exist in parent directory, try current directory (for backwards compatibility)
if (!file_exists($fullPath)) {
    $altPath = __DIR__ . '/' . $imagePath;
    error_log("get_payment_proof.php - Trying alternate path: " . $altPath);
    if (file_exists($altPath)) {
        $fullPath = $altPath;
        error_log("get_payment_proof.php - Found file at alternate path");
    }
}

// Check if file exists
if (!file_exists($fullPath)) {
    ob_clean();
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Image not found', 'path' => $imagePath]);
    ob_end_flush();
    exit;
}

// Check if it's actually a file (not a directory)
if (!is_file($fullPath)) {
    ob_clean();
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid file']);
    ob_end_flush();
    exit;
}

// Get file extension to set correct content type
$extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$contentType = 'image/jpeg'; // default
switch ($extension) {
    case 'jpg':
    case 'jpeg':
        $contentType = 'image/jpeg';
        break;
    case 'png':
        $contentType = 'image/png';
        break;
    case 'gif':
        $contentType = 'image/gif';
        break;
    case 'webp':
        $contentType = 'image/webp';
        break;
}

// Set proper headers
ob_clean();
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=3600');

// Output the file
readfile($fullPath);
ob_end_flush();
exit;
?>

