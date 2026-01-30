<?php
// Get users for messaging based on user role and boarding house relationships
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once 'db_helper.php';
header('Content-Type: application/json');

try {
    $current_user_id = $_GET['current_user_id'] ?? null;
    
    if (!$current_user_id) {
        throw new Exception('Missing required parameter: current_user_id');
    }
    
    // Debug logging
    error_log("get_users_for_messaging.php called with user_id: " . $current_user_id);
    
    $db = getDB();
    
    // Get current user's role
    $stmt = $db->prepare("
        SELECT 
            r.role as user_role,
            r.first_name,
            r.last_name
        FROM users u
        JOIN registrations r ON u.reg_id = r.id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch();
    
    if (!$current_user) {
        throw new Exception('Current user not found');
    }
    
    $formatted_users = [];
    $user_map = []; // To track unique users by user_id
    
    if ($current_user['user_role'] === 'BH Owner') {
        // OWNER SIDE: Get boarders from their own boarding houses only
        $stmt = $db->prepare("
            SELECT 
                u.user_id,
                CONCAT(r.first_name, ' ', r.last_name, 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' AND r.suffix != 'None' 
                            THEN CONCAT(' ', r.suffix) 
                            ELSE '' 
                       END) as full_name,
                r.first_name,
                r.last_name,
                r.suffix,
                r.role as user_type,
                r.email,
                r.phone,
                r.status,
                u.profile_picture,
                bh.bh_name as boarding_house_name,
                bh.bh_address as boarding_house_address,
                bh.bh_id as boarding_house_id,
                CASE 
                    WHEN dt.device_token IS NOT NULL AND dt.is_active = 1 THEN 1 
                    ELSE 0 
                END as has_device_token,
                CASE 
                    WHEN dt.device_token IS NOT NULL AND dt.is_active = 1 THEN 'Online' 
                    ELSE 'Offline' 
                END as status_text
            FROM active_boarders ab
            JOIN users u ON ab.user_id = u.user_id
            JOIN registrations r ON u.reg_id = r.id
            INNER JOIN room_units ru ON ab.room_id = ru.room_id
            INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id

            LEFT JOIN device_tokens dt ON u.user_id = dt.user_id AND dt.is_active = 1
            WHERE bh.user_id = ? -- owner_id
              AND ab.user_id != ? 
              AND ab.status = 'Active'
              AND r.role = 'Boarder' 
              AND r.status = 'approved'
            ORDER BY r.first_name ASC
        ");
        
        $stmt->execute([$current_user_id, $current_user_id]);
        $users = $stmt->fetchAll();
        
        // Format users and deduplicate by user_id
        foreach ($users as $user) {
            $user_id = (int)$user['user_id'];
            
            // If user already exists, skip (keep first occurrence)
            if (isset($user_map[$user_id])) {
                continue;
            }
            
            // Ensure full first name is used (not truncated) and include suffix
            $full_first_name = trim($user['first_name']);
            $full_last_name = trim($user['last_name']);
            $suffix = isset($user['suffix']) ? trim($user['suffix']) : '';
            
            // Build full name with suffix if present
            $full_name = $full_first_name . ' ' . $full_last_name;
            if (!empty($suffix) && strtolower($suffix) !== 'none') {
                $full_name .= ' ' . $suffix;
            }
            
            $user_data = [
                'user_id' => $user_id,
                'full_name' => $full_name,
                'user_type' => $user['user_type'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'status' => $user['status'],
                'profile_picture' => isset($user['profile_picture']) ? $user['profile_picture'] : '',
                'has_device_token' => (bool)$user['has_device_token'],
                'status_text' => $user['status_text'],
                'boarding_house_name' => $user['boarding_house_name'],
                'boarding_house_address' => $user['boarding_house_address'],
                'boarding_house_id' => (int)$user['boarding_house_id']
            ];
            
            $formatted_users[] = $user_data;
            $user_map[$user_id] = true; // Mark as added
        }
        
    } else if ($current_user['user_role'] === 'Boarder') {
        // BOARDER SIDE: Get owner and other boarders from the same boarding house
        $stmt = $db->prepare("
            SELECT 
                bh.bh_id as boarding_house_id,
                bh.bh_name,
                bh.bh_address,
                bh.user_id as owner_id
            FROM active_boarders ab
            INNER JOIN room_units ru ON ab.room_id = ru.room_id
            INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE ab.user_id = ? AND ab.status = 'active'
        ");
        $stmt->execute([$current_user_id]);
        $boarder_bh_list = $stmt->fetchAll();
        
        // Process all boarding houses the boarder is in
        foreach ($boarder_bh_list as $boarder_bh) {
            // Get the owner of the boarding house
            $stmt = $db->prepare("
                SELECT 
                    u.user_id,
                    CONCAT(r.first_name, ' ', r.last_name, 
                           CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' AND r.suffix != 'None' 
                                THEN CONCAT(' ', r.suffix) 
                                ELSE '' 
                           END) as full_name,
                    r.first_name,
                    r.last_name,
                    r.suffix,
                    r.role as user_type,
                    r.email,
                    r.phone,
                    r.status,
                    u.profile_picture,
                    CASE 
                        WHEN dt.device_token IS NOT NULL AND dt.is_active = 1 THEN 1 
                        ELSE 0 
                    END as has_device_token,
                    CASE 
                        WHEN dt.device_token IS NOT NULL AND dt.is_active = 1 THEN 'Online' 
                        ELSE 'Offline' 
                    END as status_text
                FROM users u
                JOIN registrations r ON u.reg_id = r.id
                LEFT JOIN device_tokens dt ON u.user_id = dt.user_id AND dt.is_active = 1
                WHERE u.user_id = ?
                AND r.role = 'BH Owner'
                AND r.status = 'approved'
            ");
            $stmt->execute([$boarder_bh['owner_id']]);
            $owner = $stmt->fetch();
            
            if ($owner) {
                $owner_id = (int)$owner['user_id'];
                
                // Only add if not already in the list
                if (!isset($user_map[$owner_id])) {
                    // Ensure full first name is used and include suffix
                    $full_first_name = trim($owner['first_name']);
                    $full_last_name = trim($owner['last_name']);
                    $suffix = isset($owner['suffix']) ? trim($owner['suffix']) : '';
                    
                    // Build full name with suffix if present
                    $full_name = $full_first_name . ' ' . $full_last_name;
                    if (!empty($suffix) && strtolower($suffix) !== 'none') {
                        $full_name .= ' ' . $suffix;
                    }
                    
                    $formatted_users[] = [
                        'user_id' => $owner_id,
                        'full_name' => $full_name,
                        'user_type' => $owner['user_type'],
                        'email' => $owner['email'],
                        'phone' => $owner['phone'],
                        'status' => $owner['status'],
                        'profile_picture' => isset($owner['profile_picture']) ? $owner['profile_picture'] : '',
                        'has_device_token' => (bool)$owner['has_device_token'],
                        'status_text' => $owner['status_text'],
                        'boarding_house_name' => $boarder_bh['bh_name'],
                        'boarding_house_address' => $boarder_bh['bh_address'],
                        'boarding_house_id' => (int)$boarder_bh['boarding_house_id']
                    ];
                    
                    $user_map[$owner_id] = true; // Mark as added
                }
            }
            
            // Get other boarders from the same boarding house
            $stmt = $db->prepare("
                SELECT 
                    u.user_id,
                    CONCAT(r.first_name, ' ', r.last_name, 
                           CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' AND r.suffix != 'None' 
                                THEN CONCAT(' ', r.suffix) 
                                ELSE '' 
                           END) as full_name,
                    r.first_name,
                    r.last_name,
                    r.suffix,
                    r.role as user_type,
                    r.email,
                    r.phone,
                    r.status,
                    u.profile_picture,
                    CASE 
                        WHEN dt.device_token IS NOT NULL AND dt.is_active = 1 THEN 1 
                        ELSE 0 
                    END as has_device_token,
                    CASE 
                        WHEN dt.device_token IS NOT NULL AND dt.is_active = 1 THEN 'Online' 
                        ELSE 'Offline' 
                    END as status_text
                FROM active_boarders ab
                JOIN users u ON ab.user_id = u.user_id
                JOIN registrations r ON u.reg_id = r.id
                LEFT JOIN device_tokens dt ON u.user_id = dt.user_id AND dt.is_active = 1
                INNER JOIN room_units ru ON ab.room_id = ru.room_id
                INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE bh.bh_id = ? AND ab.user_id != ? AND ab.status = 'Active' AND r.role = 'Boarder' AND r.status = 'approved'
                ORDER BY r.first_name ASC
            ");
            $stmt->execute([$boarder_bh['boarding_house_id'], $current_user_id]);
            $other_boarders = $stmt->fetchAll();
            
            foreach ($other_boarders as $boarder) {
                $boarder_id = (int)$boarder['user_id'];
                
                // Only add if not already in the list (deduplicate)
                if (!isset($user_map[$boarder_id])) {
                    // Ensure full first name is used and include suffix
                    $full_first_name = trim($boarder['first_name']);
                    $full_last_name = trim($boarder['last_name']);
                    $suffix = isset($boarder['suffix']) ? trim($boarder['suffix']) : '';
                    
                    // Build full name with suffix if present
                    $full_name = $full_first_name . ' ' . $full_last_name;
                    if (!empty($suffix) && strtolower($suffix) !== 'none') {
                        $full_name .= ' ' . $suffix;
                    }
                    
                    $formatted_users[] = [
                        'user_id' => $boarder_id,
                        'full_name' => $full_name,
                        'user_type' => $boarder['user_type'],
                        'email' => $boarder['email'],
                        'phone' => $boarder['phone'],
                        'status' => $boarder['status'],
                        'profile_picture' => isset($boarder['profile_picture']) ? $boarder['profile_picture'] : '',
                        'has_device_token' => (bool)$boarder['has_device_token'],
                        'status_text' => $boarder['status_text'],
                        'boarding_house_name' => $boarder_bh['bh_name'],
                        'boarding_house_address' => $boarder_bh['bh_address'],
                        'boarding_house_id' => (int)$boarder_bh['boarding_house_id']
                    ];
                    
                    $user_map[$boarder_id] = true; // Mark as added
                }
            }
        }
    }
    
    // Sort by Active Status first, then by full name
    usort($formatted_users, function($a, $b) {
        // Priority 1: Online Status (Active/Online first)
        $is_online_a = ($a['status_text'] === 'Online' || $a['has_device_token'] === true);
        $is_online_b = ($b['status_text'] === 'Online' || $b['has_device_token'] === true);

        if ($is_online_a && !$is_online_b) return -1; // A comes first
        if (!$is_online_a && $is_online_b) return 1;  // B comes first

        // Priority 2: Alphabetical by Name
        return strcasecmp($a['full_name'], $b['full_name']);
    });
    
    $response = [
        'success' => true,
        'data' => [
            'users' => $formatted_users,
            'total_count' => count($formatted_users),
            'current_user_id' => (int)$current_user_id,
            'current_user_role' => $current_user['user_role'],
            'has_data' => count($formatted_users) > 0
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

