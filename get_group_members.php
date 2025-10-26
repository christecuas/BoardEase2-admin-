<?php
// Get group members
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once '../db_helper.php';

header('Content-Type: application/json');

try {
    $group_id = $_GET['group_id'] ?? null;
    
    if (!$group_id) {
        throw new Exception('Missing required parameter: group_id');
    }
    
    $db = getDB();
    
    // Get group information
    $stmt = $db->prepare("
        SELECT gc_id, gc_name, gc_created_by, gc_created_at 
        FROM chat_groups 
        WHERE gc_id = ?
    ");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();
    
    if (!$group) {
        throw new Exception('Group not found');
    }
    
    // Get group members
    $stmt = $db->prepare("
        SELECT 
            u.user_id,
            r.first_name,
            r.last_name,
            r.role as user_type,
            r.email,
            r.phone as phone,
            u.status,
            u.profile_picture,
            dt.device_token,
            gm.gm_role,
            gm.gm_joined_at as joined_at
        FROM group_members gm
        JOIN users u ON gm.user_id = u.user_id
        JOIN registrations r ON u.reg_id = r.id
        LEFT JOIN device_tokens dt ON u.user_id = dt.user_id AND dt.is_active = 1
        WHERE gm.gc_id = ?
        ORDER BY gm.gm_joined_at ASC
    ");
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll();
    
    // Format members for response
    $formatted_members = [];
    foreach ($members as $member) {
        $formatted_members[] = [
            'user_id' => $member['user_id'],
            'first_name' => $member['first_name'],
            'last_name' => $member['last_name'],
            'full_name' => $member['first_name'] . ' ' . $member['last_name'],
            'user_type' => $member['user_type'],
            'email' => $member['email'],
            'phone' => $member['phone'],
            'status' => $member['status'],
            'profile_picture' => $member['profile_picture'],
            'has_device_token' => !empty($member['device_token']),
            'role' => $member['gm_role'],
            'joined_at' => $member['joined_at'],
            'is_creator' => $member['user_id'] == $group['gc_created_by']
        ];
    }
    
    $response = [
        'success' => true,
        'data' => [
            'group' => [
                'group_id' => $group['gc_id'],
                'group_name' => $group['gc_name'],
                'created_by' => $group['gc_created_by'],
                'created_at' => $group['gc_created_at']
            ],
            'members' => $formatted_members,
            'member_count' => count($formatted_members)
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




