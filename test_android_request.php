<?php
// Test Android request format
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

header('Content-Type: application/json');

try {
    // Log all request data
    error_log("=== ANDROID REQUEST DEBUG ===");
    error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set'));
    error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    error_log("CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set'));
    
    // Get raw input
    $input = file_get_contents('php://input');
    error_log("RAW INPUT: " . $input);
    error_log("RAW INPUT LENGTH: " . strlen($input));
    
    // Get POST data
    error_log("POST DATA: " . json_encode($_POST));
    
    // Try to decode JSON
    $json_data = json_decode($input, true);
    error_log("JSON DECODE RESULT: " . json_encode($json_data));
    error_log("JSON LAST ERROR: " . json_last_error_msg());
    
    $response = [
        'success' => true,
        'message' => 'Request received successfully',
        'data' => [
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'not set',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'raw_input' => $input,
            'raw_input_length' => strlen($input),
            'post_data' => $_POST,
            'json_data' => $json_data,
            'json_error' => json_last_error_msg()
        ]
    ];
    
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => null
    ];
}

ob_clean();
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
