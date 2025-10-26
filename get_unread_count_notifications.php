<?php
require_once 'db_connection.php';
require_once 'fcm_config.php';

// Get user_id from POST request
$userId = $_POST['user_id'] ?? null;

if (!$userId) {
    sendResponse(false, 'User ID is required');
}

// Validate user
$user = validateUser($pdo, $userId);
if (!$user) {
    sendResponse(false, 'Invalid user');
}

try {
    // Get current user's role for role-based filtering
    $roleQuery = "
        SELECT r.role as user_role
        FROM users u
        JOIN registrations r ON u.reg_id = r.id
        WHERE u.user_id = :user_id
    ";
    $stmt = $pdo->prepare($roleQuery);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $current_user = $stmt->fetch();
    
    // Get unread individual messages count with role-based filtering
    $individualUnreadQuery = "
        SELECT COUNT(*) as unread_count
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        JOIN registrations r ON u.reg_id = r.id
        WHERE m.receiver_id = :user_id 
        AND m.msg_status != 'Read'
        AND (
            (:user_role = 'BH Owner' AND u.user_id IN (
                SELECT ab.user_id 
                FROM active_boarders ab 
                JOIN boarding_houses bh ON ab.boarding_house_id = bh.bh_id
                WHERE bh.user_id = :user_id 
                AND ab.status = 'Active'
                AND ab.user_id != :user_id
            ) AND r.role = 'Boarder' AND r.status = 'approved')
            OR
            (:user_role = 'Boarder' AND (
                u.user_id IN (
                    SELECT bh.user_id 
                    FROM active_boarders ab 
                    JOIN boarding_houses bh ON ab.boarding_house_id = bh.bh_id
                    WHERE ab.user_id = :user_id AND ab.status = 'Active'
                )
                OR u.user_id IN (
                    SELECT ab2.user_id 
                    FROM active_boarders ab1
                    JOIN active_boarders ab2 ON ab1.boarding_house_id = ab2.boarding_house_id
                    WHERE ab1.user_id = :user_id 
                    AND ab1.status = 'Active' 
                    AND ab2.status = 'Active'
                    AND ab2.user_id != :user_id
                )
            ))
        )
    ";

    $stmt = $pdo->prepare($individualUnreadQuery);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':user_role', $current_user['user_role'], PDO::PARAM_STR);
    $stmt->execute();
    $individualUnread = $stmt->fetch();

    // Get unread group messages count with role-based filtering
    $groupUnreadQuery = "
        SELECT COUNT(*) as unread_count
        FROM group_messages gm
        JOIN group_members gm_member ON gm.gc_id = gm_member.gc_id
        JOIN users u ON gm.sender_id = u.user_id
        JOIN registrations r ON u.reg_id = r.id
        WHERE gm_member.user_id = :user_id 
        AND gm.sender_id != :user_id 
        AND gm.groupmessage_status != 'Read'
        AND (
            (:user_role = 'BH Owner' AND u.user_id IN (
                SELECT ab.user_id 
                FROM active_boarders ab 
                JOIN boarding_houses bh ON ab.boarding_house_id = bh.bh_id
                WHERE bh.user_id = :user_id 
                AND ab.status = 'Active'
                AND ab.user_id != :user_id
            ) AND r.role = 'Boarder' AND r.status = 'approved')
            OR
            (:user_role = 'Boarder' AND (
                u.user_id IN (
                    SELECT bh.user_id 
                    FROM active_boarders ab 
                    JOIN boarding_houses bh ON ab.boarding_house_id = bh.bh_id
                    WHERE ab.user_id = :user_id AND ab.status = 'Active'
                )
                OR u.user_id IN (
                    SELECT ab2.user_id 
                    FROM active_boarders ab1
                    JOIN active_boarders ab2 ON ab1.boarding_house_id = ab2.boarding_house_id
                    WHERE ab1.user_id = :user_id 
                    AND ab1.status = 'Active' 
                    AND ab2.status = 'Active'
                    AND ab2.user_id != :user_id
                )
            ))
        )
    ";

    $stmt = $pdo->prepare($groupUnreadQuery);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':user_role', $current_user['user_role'], PDO::PARAM_STR);
    $stmt->execute();
    $groupUnread = $stmt->fetch();

    // Get unread system notifications count
    $systemUnreadQuery = "
        SELECT COUNT(*) as unread_count
        FROM notifications 
        WHERE receiver_id = :user_id 
        AND is_read = 0
        AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ";

    $stmt = $pdo->prepare($systemUnreadQuery);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $systemUnread = $stmt->fetch();

    // Calculate total unread count
    $totalUnread = (int)$individualUnread['unread_count'] + (int)$groupUnread['unread_count'];

    // Get detailed breakdown
    $unreadBreakdown = [
        'individual' => (int)$individualUnread['unread_count'],
        'group' => (int)$groupUnread['unread_count'],
        'system' => (int)$systemUnread['unread_count'],
        'total' => $totalUnread,
        'last_checked' => date('Y-m-d H:i:s')
    ];

    // If there are unread messages, send a data-only notification to update badge
    if ($totalUnread > 0) {
        // Get user's device tokens
        $tokensQuery = "
            SELECT device_token 
            FROM device_tokens 
            WHERE user_id = :user_id 
            AND is_active = 1 
            AND last_used > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";

        $stmt = $pdo->prepare($tokensQuery);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Send data-only message to update badge
        if (!empty($tokens)) {
            $badgeData = [
                'type' => 'badge_update',
                'unread_count' => $totalUnread,
                'individual_count' => (int)$individualUnread['unread_count'],
                'group_count' => (int)$groupUnread['unread_count'],
                'timestamp' => date('Y-m-d H:i:s')
            ];

            foreach ($tokens as $token) {
                FCMConfig::sendDataMessage($token, $badgeData);
            }
        }
    }

    sendResponse(true, 'Unread count retrieved successfully', $unreadBreakdown);

} catch (Exception $e) {
    sendResponse(false, 'Error retrieving unread count: ' . $e->getMessage());
}
?>






















