<?php
// Add members to group
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db_helper.php';
require_once 'activity_notifications.php'; // Assuming this exists for notifications

header('Content-Type: application/json');

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $group_id = $data['group_id'] ?? null;
    $member_ids = $data['member_ids'] ?? []; // Array of user IDs
    $added_by = $data['added_by'] ?? null;   // User ID of who is adding
    
    if (!$group_id || empty($member_ids)) {
        throw new Exception('Missing required parameters: group_id or member_ids');
    }
    
    $db = getDB();
    
    // Validate group exists
    $stmt = $db->prepare("SELECT gc_name FROM chat_groups WHERE gc_id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();
    
    if (!$group) {
        throw new Exception('Group not found');
    }
    
    $success_count = 0;
    $errors = [];
    
    // Prepare insert statement
    $stmt_insert = $db->prepare("
        INSERT INTO group_members (gc_id, user_id, gm_role, gm_joined_at) 
        VALUES (?, ?, 'Member', NOW())
    ");
    
    foreach ($member_ids as $user_id) {
        try {
            // Check if already a member
            $check = $db->prepare("SELECT gm_id FROM group_members WHERE gc_id = ? AND user_id = ?");
            $check->execute([$group_id, $user_id]);
            
            if ($check->rowCount() == 0) {
                if ($stmt_insert->execute([$group_id, $user_id])) {
                    $success_count++;
                    
                    // Optional: Send notification to the user that they were added
                    // This depends on your notification system structure
                    /*
                    if (class_exists('ActivityNotifications')) {
                         ActivityNotifications::notifyGroupAdd($user_id, $group['gc_name']);
                    }
                    */
                }
            }
        } catch (Exception $e) {
            $errors[] = "Failed to add user $user_id: " . $e->getMessage();
        }
    }
    
    if ($success_count == 0 && count($member_ids) > 0) {
        throw new Exception('Failed to add any members. They might already be in the group.');
    }
    
    $response = [
        'success' => true,
        'message' => "Successfully added $success_count members.",
        'data' => [
            'added_count' => $success_count,
            'errors' => $errors
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => null
    ];
}

ob_clean();
echo json_encode($response);
exit;
?>
