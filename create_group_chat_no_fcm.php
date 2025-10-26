<?php
// Create a new group chat (without FCM notifications)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_helper.php';

header('Content-Type: application/json');

try {
    // Handle both JSON and regular POST data
    $group_name = null;
    $created_by = null;
    $members = [];
    
    // Check if JSON data is sent
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
        if ($data) {
            $group_name = $data['group_name'] ?? null;
            $created_by = $data['created_by'] ?? null;
            $members = $data['member_ids'] ?? $data['members'] ?? [];
        }
    }
    
    // Fallback to regular POST data
    if (!$group_name || !$created_by || empty($members)) {
        $group_name = $_POST['group_name'] ?? null;
        $created_by = $_POST['created_by'] ?? null;
        $members = $_POST['member_ids'] ?? $_POST['members'] ?? [];
    }
    
    // If members is a JSON string, decode it
    if (is_string($members)) {
        $members = json_decode($members, true);
    }
    
    // Validate input
    if (!$group_name || !$created_by || empty($members)) {
        throw new Exception('Missing required parameters: group_name, created_by, member_ids');
    }
    
    // Add creator to members if not already included
    if (!in_array($created_by, $members)) {
        $members[] = $created_by;
    }
    
    $db = getDB();
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Create group
        $stmt = $db->prepare("
            INSERT INTO chat_groups (bh_id, gc_name, gc_created_by, gc_created_at) 
            VALUES (1, ?, ?, NOW())
        ");
        $stmt->execute([$group_name, $created_by]);
        $group_id = $db->lastInsertId();
        
        // Add members to group
        $stmt = $db->prepare("
            INSERT INTO group_members (gc_id, user_id, gm_role, gm_joined_at) 
            VALUES (?, ?, 'Boarder', NOW())
        ");
        
        foreach ($members as $member_id) {
            $stmt->execute([$group_id, $member_id]);
        }
        
        // Commit transaction
        $db->commit();
        
        $response = [
            'success' => true,
            'message' => 'Group chat created successfully',
            'data' => [
                'group_id' => $group_id,
                'group_name' => $group_name,
                'created_by' => $created_by,
                'member_count' => count($members),
                'notifications_sent' => 0,
                'total_members' => count($members)
            ]
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => null
    ];
}

echo json_encode($response);
exit;
?>
