<?php
// Get non-group members (eligible to be added)
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db_helper.php';

header('Content-Type: application/json');

try {
    $group_id = $_GET['group_id'] ?? null;
    $current_user_id = $_GET['current_user_id'] ?? null; // The person trying to add (to check permissions/BH scope)
    
    if (!$group_id) {
        throw new Exception('Missing required parameter: group_id');
    }
    
    $db = getDB();
    
    // 1. Get Group Info to determine the Boarding House context
    // Assuming the group creator is the BH Owner
    $stmt = $db->prepare("
        SELECT gc_created_by, gc_name 
        FROM chat_groups 
        WHERE gc_id = ?
    ");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();
    
    if (!$group) {
        throw new Exception('Group not found');
    }
    
    $owner_id = $group['gc_created_by'];
    
    // 2. Get active boarders of this owner who are NOT already in the group
    // We also join with boarding_houses to get the BH name
    $stmt = $db->prepare("
        SELECT 
            u.user_id,
            r.first_name,
            r.last_name,
            r.email,
            r.phone,
            u.profile_picture,
            bh.bh_name AS boarding_house_name
        FROM active_boarders ab
        JOIN room_units ru ON ab.room_id = ru.room_id
        JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        JOIN users u ON ab.user_id = u.user_id
        JOIN registrations r ON u.reg_id = r.id
        WHERE bh.user_id = ?        -- Belonging to the group creator (Owner)
        AND ab.status = 'Active'    -- Only active boarders
        AND u.user_id NOT IN (      -- Exclude existing group members
            SELECT user_id 
            FROM group_members 
            WHERE gc_id = ?
        )
        ORDER BY r.first_name ASC
    ");
    
    $stmt->execute([$owner_id, $group_id]);
    $potential_members = $stmt->fetchAll();
    
    // Format for response
    $formatted_users = [];
    foreach ($potential_members as $user) {
        $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
        
        $formatted_users[] = [
            'user_id' => $user['user_id'],
            'full_name' => $fullName,
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'profile_picture' => $user['profile_picture'],
            'boarding_house_name' => $user['boarding_house_name'] // Included as requested
        ];
    }
    
    $response = [
        'success' => true,
        'data' => [
            'group_name' => $group['gc_name'],
            'users' => $formatted_users,
            'count' => count($formatted_users)
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
