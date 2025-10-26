<?php
// Simple debug for create_group_chat.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

header('Content-Type: application/json');

$debug_info = [];

try {
    $debug_info[] = "Starting debug...";
    
    // Test 1: Basic PHP
    $debug_info[] = "✅ Basic PHP working";
    
    // Test 2: Check if db_helper.php exists
    if (!file_exists('db_helper.php')) {
        throw new Exception('db_helper.php not found');
    }
    $debug_info[] = "✅ db_helper.php exists";
    
    // Test 3: Load db_helper.php
    require_once 'db_helper.php';
    $debug_info[] = "✅ db_helper.php loaded";
    
    // Test 4: Database connection
    $db = getDB();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    $debug_info[] = "✅ Database connection successful";
    
    // Test 5: Check boarding_houses table
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM boarding_houses");
    $stmt->execute();
    $result = $stmt->fetch();
    $debug_info[] = "✅ Boarding houses count: " . $result['count'];
    
    // Test 6: Get first boarding house
    $stmt = $db->prepare("SELECT bh_id FROM boarding_houses LIMIT 1");
    $stmt->execute();
    $bh_result = $stmt->fetch();
    $bh_id = $bh_result ? $bh_result['bh_id'] : null;
    $debug_info[] = "✅ First bh_id: " . ($bh_id ?? 'null');
    
    $response = [
        'success' => true,
        'message' => 'Debug completed successfully',
        'data' => [
            'debug_info' => $debug_info,
            'bh_id_found' => $bh_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
} catch (Exception $e) {
    $debug_info[] = "❌ Error: " . $e->getMessage();
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => [
            'debug_info' => $debug_info
        ]
    ];
}

ob_clean();
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
