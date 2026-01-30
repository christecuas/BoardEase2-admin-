<?php
// get_chat_details.php
// Get details for a specific chat (e.g., online status)
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db_helper.php';

header('Content-Type: application/json');

try {
    $other_user_id = $_GET['other_user_id'] ?? null;
    
    if (!$other_user_id) {
        throw new Exception('Missing required parameter: other_user_id');
    }
    
    $db = getDB();
    
    // Check online status using device_tokens
    $stmt = $db->prepare("
        SELECT 
            CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM device_tokens 
                    WHERE user_id = ? 
                    AND is_active = 1 
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                ) THEN 'Active'
                ELSE 'Offline'
            END as online_status
    ");
    
    $stmt->execute([$other_user_id]);
    $result = $stmt->fetch();
    
    $online_status = $result['online_status'] ?? 'Offline';
    
    $response = [
        'success' => true,
        'data' => [
            'other_user_id' => (int)$other_user_id,
            'online_status' => $online_status,
            'is_online' => ($online_status === 'Active')
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

ob_clean();
echo json_encode($response);
?>
