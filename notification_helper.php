<?php
// Enable error reporting and logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Notification helper functions
require_once __DIR__ . '/db_helper.php';
require_once __DIR__ . '/fcm_config.php';

class NotificationHelper {
    
    public static function createNotification($user_id, $title, $message, $type = 'general', $send_fcm = true) {
        error_log("============================================");
        error_log("NotificationHelper::createNotification() START");
        error_log("============================================");
        error_log("NotificationHelper - Input parameters:");
        error_log("  - user_id: " . var_export($user_id, true) . " (type: " . gettype($user_id) . ")");
        error_log("  - title: $title");
        error_log("  - message: $message");
        error_log("  - type: $type");
        error_log("  - send_fcm: " . var_export($send_fcm, true));
        
        try {
            $db = getDB();
            error_log("NotificationHelper - Database connection obtained");
            
            // Validate inputs
            if (empty($user_id) || !is_numeric($user_id)) {
                error_log("NotificationHelper ERROR: Invalid user_id provided: " . var_export($user_id, true));
                return [
                    'success' => false,
                    'message' => 'Invalid user_id',
                    'data' => null
                ];
            }
            
            error_log("NotificationHelper - Resolving user_id: $user_id");
            // Resolve user_id - handle both users.user_id and registrations.id
            $actual_user_id = self::resolveUserId($db, $user_id);
            error_log("NotificationHelper - Resolved user_id: " . var_export($actual_user_id, true));
            
            if (!$actual_user_id) {
                error_log("NotificationHelper ERROR: Could not resolve user_id: $user_id (type: " . gettype($user_id) . ")");
                error_log("NotificationHelper ERROR: User not found in database");
                return [
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null
                ];
            }
            
            error_log("NotificationHelper - Using resolved user_id: $actual_user_id");
            
            // Log notification creation for debugging (always log for booking type)
            if (in_array($type, ['registration_approved', 'registration_rejected', 'booking'])) {
                error_log("NotificationHelper: Creating notification for user_id=$actual_user_id, type=$type, title='$title'");
            }
            
            error_log("NotificationHelper - Inserting notification into database...");
            // Insert notification into database (notifications.user_id refers to users.user_id)
            // IMPORTANT: This INSERT only creates a notification for the specific user_id
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status, notif_created_at) 
                VALUES (?, ?, ?, ?, 'unread', NOW())
            ");
            error_log("NotificationHelper - Prepared INSERT statement");
            error_log("NotificationHelper - Executing with params: user_id=$actual_user_id, title='$title', message='$message', type='$type'");
            
            $stmt->execute([$actual_user_id, $title, $message, $type]);
            $notif_id = $db->lastInsertId();
            error_log("NotificationHelper - Notification inserted successfully, notif_id: $notif_id");
            
            // Verify the notification was created for the correct user
            error_log("NotificationHelper - Verifying notification was created correctly...");
            $verifyStmt = $db->prepare("SELECT user_id, notif_id, notif_title, notif_message, notif_type FROM notifications WHERE notif_id = ?");
            $verifyStmt->execute([$notif_id]);
            $verifyResult = $verifyStmt->fetch();
            
            if ($verifyResult) {
                error_log("NotificationHelper - Verification successful:");
                error_log("  - notif_id: " . $verifyResult['notif_id']);
                error_log("  - user_id: " . $verifyResult['user_id']);
                error_log("  - notif_title: " . $verifyResult['notif_title']);
                error_log("  - notif_message: " . $verifyResult['notif_message']);
                error_log("  - notif_type: " . $verifyResult['notif_type']);
                
                if ($verifyResult['user_id'] != $actual_user_id) {
                    error_log("NotificationHelper ERROR: Notification created for wrong user! Expected user_id=$actual_user_id but got user_id=" . $verifyResult['user_id']);
                } else {
                    error_log("NotificationHelper - Verification: user_id matches correctly");
                }
            } else {
                error_log("NotificationHelper ERROR: Could not verify notification creation - notif_id $notif_id not found!");
            }
            
            error_log("NotificationHelper - Getting user information for FCM...");
            // Get user information for FCM - ONLY for the specific user_id
            // NOTE: We don't filter by status here - we want to send notifications even if user is not Active/approved
            // The app can decide whether to display the notification based on user status
            $stmt = $db->prepare("
                SELECT 
                    u.user_id, 
                    u.reg_id,
                    u.status as user_status,
                    r.first_name, 
                    r.last_name, 
                    r.email, 
                    r.status as reg_status,
                    r.role,
                    dt.device_token 
                FROM users u 
                LEFT JOIN registrations r ON u.reg_id = r.id 
                LEFT JOIN device_tokens dt ON u.user_id = dt.user_id AND dt.is_active = 1 
                WHERE u.user_id = ?
                LIMIT 1
            ");
            error_log("NotificationHelper - Executing user query for user_id: $actual_user_id");
            $stmt->execute([$actual_user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("NotificationHelper - User found:");
                error_log("  - user_id: " . $user['user_id']);
                error_log("  - reg_id: " . ($user['reg_id'] ?? 'NULL'));
                error_log("  - name: " . ($user['first_name'] ?? '') . " " . ($user['last_name'] ?? ''));
                error_log("  - email: " . ($user['email'] ?? 'NULL'));
                error_log("  - role: " . ($user['role'] ?? 'NULL'));
                error_log("  - reg_status: " . ($user['reg_status'] ?? 'NULL'));
                error_log("  - user_status: " . ($user['user_status'] ?? 'NULL'));
                error_log("  - device_token: " . ($user['device_token'] ? substr($user['device_token'], 0, 20) . "..." : 'NULL'));
                
                // Check if user is active and approved - log warnings but don't block notification
                if (isset($user['user_status']) && $user['user_status'] != 'Active') {
                    error_log("NotificationHelper WARNING: User status is not Active: " . $user['user_status']);
                }
                if (isset($user['reg_status']) && $user['reg_status'] != 'approved') {
                    error_log("NotificationHelper WARNING: Registration status is not approved: " . $user['reg_status']);
                }
                
                // Check if user has registration record
                if (!isset($user['reg_id']) || !$user['reg_id']) {
                    error_log("NotificationHelper WARNING: User has no registration record (reg_id is NULL)");
                }
            } else {
                error_log("NotificationHelper ERROR: User not found for user_id: $actual_user_id");
                error_log("NotificationHelper ERROR: Query returned no results - user may not exist in users table");
            }
            
            // Verify we're only sending to the intended user
            if ($user && $user['user_id'] != $actual_user_id) {
                error_log("NotificationHelper ERROR: FCM query returned wrong user_id! Expected $actual_user_id but got " . $user['user_id']);
                $user = null; // Don't send FCM to wrong user
            }
            
            $fcm_sent = false;
            $fcm_result = null;
            
            // Send FCM notification if requested and user has device token
            // IMPORTANT: This only sends to the single user specified by $actual_user_id
            if ($send_fcm && $user && $user['device_token']) {
                error_log("NotificationHelper - Sending FCM notification...");
                try {
                    // Double-check: Only send if user_id matches
                    if ($user['user_id'] == $actual_user_id) {
                        error_log("NotificationHelper - Calling FCMConfig::sendToDevice()...");
                        $fcm_result = FCMConfig::sendToDevice(
                            $user['device_token'],
                            $title,
                            $message,
                            [
                                'type' => 'notification',
                                'notif_id' => (string)$notif_id,
                                'notif_type' => $type,
                                'user_id' => (string)$actual_user_id, // Ensure correct user_id in FCM data
                                'timestamp' => date('Y-m-d H:i:s')
                            ]
                        );
                        $fcm_sent = $fcm_result['success'];
                        error_log("NotificationHelper - FCM result: " . json_encode($fcm_result, JSON_UNESCAPED_SLASHES));
                        
                        if (in_array($type, ['registration_approved', 'registration_rejected', 'booking'])) {
                            error_log("NotificationHelper: FCM sent to user_id=$actual_user_id, device_token=" . substr($user['device_token'], 0, 20) . "...");
                        }
                    } else {
                        error_log("NotificationHelper ERROR: FCM user_id mismatch - not sending");
                        error_log("  - Expected user_id: $actual_user_id");
                        error_log("  - Got user_id: " . $user['user_id']);
                    }
                } catch (Exception $e) {
                    error_log("NotificationHelper ERROR: FCM send failed");
                    error_log("  - Exception: " . $e->getMessage());
                    error_log("  - File: " . $e->getFile());
                    error_log("  - Line: " . $e->getLine());
                    error_log("  - Trace: " . $e->getTraceAsString());
                    $fcm_result = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            } else {
                if (!$send_fcm) {
                    error_log("NotificationHelper: FCM not requested (send_fcm=false)");
                } elseif (!$user) {
                    error_log("NotificationHelper ERROR: User not found for FCM - user_id: $actual_user_id");
                } elseif (!$user['device_token']) {
                    error_log("NotificationHelper: User has no device token - user_id: $actual_user_id");
                } else {
                    error_log("NotificationHelper: FCM not sent for unknown reason");
                }
            }
            
            $result = [
                'success' => true,
                'message' => 'Notification created successfully',
                'data' => [
                    'notif_id' => $notif_id,
                    'user_id' => $actual_user_id,
                    'fcm_sent' => $fcm_sent,
                    'fcm_result' => $fcm_result
                ]
            ];
            
            error_log("NotificationHelper - Returning result: " . json_encode($result, JSON_UNESCAPED_SLASHES));
            error_log("============================================");
            error_log("NotificationHelper::createNotification() END");
            error_log("============================================");
            
            return $result;
            
        } catch (Exception $e) {
            error_log("============================================");
            error_log("NotificationHelper::createNotification() EXCEPTION");
            error_log("============================================");
            error_log("NotificationHelper ERROR: Exception occurred");
            error_log("  - Message: " . $e->getMessage());
            error_log("  - File: " . $e->getFile());
            error_log("  - Line: " . $e->getLine());
            error_log("  - Trace: " . $e->getTraceAsString());
            error_log("============================================");
            
            return [
                'success' => false,
                'message' => 'Error creating notification: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Resolve user_id - handles both users.user_id and registrations.id
     * Returns users.user_id (required for notifications table foreign key)
     */
    private static function resolveUserId($db, $user_id) {
        error_log("NotificationHelper::resolveUserId() - Input user_id: " . var_export($user_id, true) . " (type: " . gettype($user_id) . ")");
        
        if (!$user_id) {
            error_log("NotificationHelper::resolveUserId() - user_id is empty/null, returning null");
            return null;
        }
        
        // First, try to find by users.user_id directly
        error_log("NotificationHelper::resolveUserId() - Attempting to find user by users.user_id: $user_id");
        $stmt = $db->prepare("SELECT user_id, reg_id FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            error_log("NotificationHelper::resolveUserId() - Found user by users.user_id: " . $user['user_id']);
            return $user['user_id'];
        }
        
        error_log("NotificationHelper::resolveUserId() - User not found by users.user_id, trying registrations.id");
        // If not found, try to find by registrations.id and get users.user_id
        $stmt = $db->prepare("
            SELECT u.user_id, r.id as reg_id
            FROM registrations r 
            LEFT JOIN users u ON r.id = u.reg_id 
            WHERE r.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && $user['user_id']) {
            error_log("NotificationHelper::resolveUserId() - Found user by registrations.id, resolved user_id: " . $user['user_id']);
            return $user['user_id'];
        }
        
        error_log("NotificationHelper::resolveUserId() ERROR: Could not resolve user_id: $user_id");
        if ($user) {
            error_log("NotificationHelper::resolveUserId() - User found but user_id is null/empty");
        } else {
            error_log("NotificationHelper::resolveUserId() - No user found in database");
        }
        
        return null;
    }
}
?>
