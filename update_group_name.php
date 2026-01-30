<?php
// Update group name
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db_helper.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $group_id = $data['group_id'] ?? null;
    $new_name = trim($data['new_name'] ?? '');
    $user_id = $data['user_id'] ?? null;
    
    if (!$group_id || empty($new_name) || !$user_id) {
        throw new Exception('Missing required parameters');
    }
    
    $db = getDB();
    
    // 1. Verify group existence and ownership
    // Only the creator (Owner) can rename the group
    $stmt = $db->prepare("SELECT gc_created_by FROM chat_groups WHERE gc_id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();
    
    if (!$group) {
        throw new Exception('Group not found');
    }
    
    if ($group['gc_created_by'] != $user_id) {
        // Also allow if user is a BH Owner/Owner in general? 
        // User request: "ang owner ra dapat ang maka change" (only the owner should be able to change)
        // Sticking to creator check is safest for "Owner of this group"
        throw new Exception('Unauthorized: Only the group owner can rename the group');
    }
    
    // 2. Update the name
    $updateStmt = $db->prepare("UPDATE chat_groups SET gc_name = ? WHERE gc_id = ?");
    $result = $updateStmt->execute([$new_name, $group_id]);
    
    if ($result) {
        $response = [
            'success' => true,
            'message' => 'Group name updated successfully',
            'data' => [
                'group_id' => $group_id,
                'new_name' => $new_name
            ]
        ];
    } else {
        throw new Exception('Failed to update group name');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

ob_clean();
echo json_encode($response);
exit;
?>
