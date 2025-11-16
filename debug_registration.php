<?php
// debug_registration.php - Simple debug script to see what's being received

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

error_log("=== DEBUG REGISTRATION REQUEST ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT_SET'));
error_log("Content length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NOT_SET'));

error_log("POST data:");
error_log(print_r($_POST, true));

error_log("FILES data:");
error_log(print_r($_FILES, true));

error_log("Raw input:");
$rawInput = file_get_contents('php://input');
error_log("Raw input length: " . strlen($rawInput));
error_log("Raw input (first 500 chars): " . substr($rawInput, 0, 500));

$response = array(
    "success" => true,
    "message" => "Debug data logged successfully",
    "post_count" => count($_POST),
    "files_count" => count($_FILES),
    "raw_input_length" => strlen($rawInput)
);

echo json_encode($response);
?>
