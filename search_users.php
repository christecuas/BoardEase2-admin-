<?php
// Search users for messaging
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'db_helper.php';

header('Content-Type: application/json');

try {
    $current_user_id = $_GET['current_user_id'] ?? null;
    $search_term = $_GET['search_term'] ?? '';
    $user_type = $_GET['user_type'] ?? ''; // Optional filter by user type
    
    // Validate input
    if (!$current_user_id) {
        throw new Exception('Missing required parameter: current_user_id');
    }
    
    if (empty($search_term)) {
        throw new Exception('Search term cannot be empty');
    }
    
    $db = getDB();
    
    // Get current user's role first
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
    
    // Search users based on role and boarding house relationships
    if ($current_user['user_role'] === 'BH Owner') {
        // OWNER SIDE: Search only boarders from their own boarding houses
        $stmt = $db->prepare("
            SELECT 
                u.user_id,
                r.first_name,
                r.last_name,
                r.suffix,
                r.role as user_type,
                r.email,
                r.phone as phone,
                u.status,
                u.profile_picture,
                dt.device_token,
                bh.bh_name as boarding_house_name,
                bh.bh_address as boarding_house_address,
                bh.bh_id as boarding_house_id,
                CASE WHEN dt.device_token IS NOT NULL THEN 1 ELSE 0 END as is_online
            FROM users u
            JOIN registrations r ON u.reg_id = r.id
            JOIN active_boarders ab ON u.user_id = ab.user_id
            INNER JOIN room_units ru ON ab.room_id = ru.room_id
            INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            LEFT JOIN device_tokens dt ON u.user_id = dt.user_id AND dt.is_active = 1
            WHERE bh.bh_id IN (
                SELECT bh_id FROM boarding_houses WHERE user_id = ?
            )
            AND ab.user_id != ?
            AND ab.status = 'Active'
            AND r.role = 'Boarder'
            AND r.status = 'approved'
            AND (r.first_name LIKE ? OR r.last_name LIKE ? OR CONCAT(r.first_name, ' ', r.last_name, 
                CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' AND r.suffix != 'None' 
                     THEN CONCAT(' ', r.suffix) 
                     ELSE '' 
                END) LIKE ? OR r.email LIKE ?)
            ORDER BY 
                CASE 
                    WHEN r.first_name LIKE ? THEN 1
                    WHEN r.last_name LIKE ? THEN 2
                    WHEN CONCAT(r.first_name, ' ', r.last_name, 
                        CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' AND r.suffix != 'None' 
                             THEN CONCAT(' ', r.suffix) 
                             ELSE '' 
                        END) LIKE ? THEN 3
                    ELSE 4
                END,
                is_online DESC,
                r.first_name ASC
            LIMIT 20
        ");
        
        $search_pattern = '%' . $search_term . '%';
        $search_exact = $search_term . '%';
        $stmt->execute([
            $current_user_id, $current_user_id, 
            $search_pattern, $search_pattern, $search_pattern, $search_pattern,
            $search_exact, $search_exact, $search_exact
        ]);
        $users = $stmt->fetchAll();
        
        // Format users and deduplicate by user_id
        foreach ($users as $user) {
            $user_id = (int)$user['user_id'];
            
            // If user already exists, skip (keep first occurrence)
            if (isset($user_map[$user_id])) {
                continue;
            }
            
            // Build full name with suffix
            $first_name = trim($user['first_name']);
            $last_name = trim($user['last_name']);
            $suffix = isset($user['suffix']) ? trim($user['suffix']) : '';
            
            $fullName = $first_name . ' ' . $last_name;
            if (!empty($suffix) && strtolower($suffix) !== 'none') {
                $fullName .= ' ' . $suffix;
            }
            
            $formatted_users[] = [
                'user_id' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'full_name' => $fullName,
                'user_type' => $user['user_type'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'status' => $user['status'],
                'profile_picture' => isset($user['profile_picture']) ? $user['profile_picture'] : '',
                'has_device_token' => !empty($user['device_token']),
                'is_online' => (bool)$user['is_online'],
                'boarding_house_name' => $user['boarding_house_name'] ?? '',
                'boarding_house_address' => $user['boarding_house_address'] ?? '',
                'boarding_house_id' => $user['boarding_house_id'] ?? null
            ];
            
            $user_map[$user_id] = true; // Mark as added
        }
        
    } else if ($current_user['user_role'] === 'Boarder') {
        // BOARDER SIDE: Search owner and other boarders from same boarding house
        
        // First, find which boarding houses this boarder is staying in
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
            WHERE ab.user_id = ? AND ab.status = 'Active'
        ");
        $stmt->execute([$current_user_id]);
        $boarder_bh_list = $stmt->fetchAll();
        
        // Process all boarding houses the boarder is in
        foreach ($boarder_bh_list as $boarder_bh) {
            $stmt = $db->prepare("
                SELECT 
                    u.user_id,
                    r.first_name,
                    r.last_name,
                    r.suffix,
                    r.role as user_type,
                    r.email,
                    r.phone as phone,
                    u.status,
                    u.profile_picture,
                    dt.device_token,
                    ? as boarding_house_name,
                    ? as boarding_house_address,
                    ? as boarding_house_id,
                    CASE WHEN dt.device_token IS NOT NULL THEN 1 ELSE 0 END as is_online
                FROM users u
                JOIN registrations r ON u.reg_id = r.id
                LEFT JOIN device_tokens dt ON u.user_id = dt.user_id AND dt.is_active = 1
                WHERE u.user_id != ?
                AND (u.user_id = ? OR 
                    (u.user_id IN (
                        SELECT ab2.user_id 
                        FROM active_boarders ab2
                        INNER JOIN room_units ru2 ON ab2.room_id = ru2.room_id
                        INNER JOIN boarding_house_rooms bhr2 ON ru2.bhr_id = bhr2.bhr_id
                        WHERE bhr2.bh_id = ? 
                        AND ab2.user_id != ? 
                        AND ab2.status = 'Active'
                    ) AND r.role = 'Boarder' AND r.status = 'approved'))
                AND (r.first_name LIKE ? OR r.last_name LIKE ? OR CONCAT(r.first_name, ' ', r.last_name, 
                    CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' AND r.suffix != 'None' 
                         THEN CONCAT(' ', r.suffix) 
                         ELSE '' 
                    END) LIKE ? OR r.email LIKE ?)
                ORDER BY 
                    CASE 
                        WHEN r.first_name LIKE ? THEN 1
                        WHEN r.last_name LIKE ? THEN 2
                        WHEN CONCAT(r.first_name, ' ', r.last_name, 
                            CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' AND r.suffix != 'None' 
                                 THEN CONCAT(' ', r.suffix) 
                                 ELSE '' 
                            END) LIKE ? THEN 3
                        ELSE 4
                    END,
                    is_online DESC,
                    r.first_name ASC
                LIMIT 20
            ");
            
            $search_pattern = '%' . $search_term . '%';
            $search_exact = $search_term . '%';
            $stmt->execute([
                $boarder_bh['bh_name'], $boarder_bh['bh_address'], $boarder_bh['bh_id'],
                $current_user_id, $boarder_bh['owner_id'], 
                $boarder_bh['boarding_house_id'], $current_user_id,
                $search_pattern, $search_pattern, $search_pattern, $search_pattern,
                $search_exact, $search_exact, $search_exact
            ]);
            $users = $stmt->fetchAll();
            
            // Format users and deduplicate by user_id
            foreach ($users as $user) {
                $user_id = (int)$user['user_id'];
                
                // Only add if not already in the list (deduplicate)
                if (!isset($user_map[$user_id])) {
                    // Build full name with suffix
                    $first_name = trim($user['first_name']);
                    $last_name = trim($user['last_name']);
                    $suffix = isset($user['suffix']) ? trim($user['suffix']) : '';
                    
                    $fullName = $first_name . ' ' . $last_name;
                    if (!empty($suffix) && strtolower($suffix) !== 'none') {
                        $fullName .= ' ' . $suffix;
                    }
                    
                    $formatted_users[] = [
                        'user_id' => $user_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'full_name' => $fullName,
                        'user_type' => $user['user_type'],
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                        'status' => $user['status'],
                        'profile_picture' => isset($user['profile_picture']) ? $user['profile_picture'] : '',
                        'has_device_token' => !empty($user['device_token']),
                        'is_online' => (bool)$user['is_online'],
                        'boarding_house_name' => $boarder_bh['bh_name'],
                        'boarding_house_address' => $boarder_bh['bh_address'],
                        'boarding_house_id' => (int)$boarder_bh['bh_id']
                    ];
                    
                    $user_map[$user_id] = true; // Mark as added
                }
            }
        }
    } else {
        // Unknown role - return empty
        $formatted_users = [];
    }
    
    // Sort by full name for consistent ordering
    usort($formatted_users, function($a, $b) {
        return strcasecmp($a['full_name'], $b['full_name']);
    });
    
    $response = [
        'success' => true,
        'data' => [
            'users' => $formatted_users,
            'search_term' => $search_term,
            'user_type_filter' => $user_type,
            'total_count' => count($formatted_users)
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

