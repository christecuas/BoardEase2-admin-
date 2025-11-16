<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $limit = intval($_GET['limit'] ?? 50);
    
    $securityEvents = [];
    
    // 1. Admin Login History (from admin_activity_log table)
    try {
        // First check if table has any login records
        $checkLoginCount = $conn->query("SELECT COUNT(*) as count FROM admin_activity_log WHERE activity_type = 'login'");
        $loginCount = 0;
        if ($checkLoginCount) {
            $countRow = $checkLoginCount->fetch_assoc();
            $loginCount = (int)$countRow['count'];
        }
        
        if ($loginCount > 0) {
            $loginSql = "SELECT 
                            aal.activity_id,
                            aal.admin_id,
                            aa.name,
                            aa.email,
                            DATE_FORMAT(aal.created_at, '%Y-%m-%d %H:%i:%s') as last_login,
                            aal.created_at as raw_created_at,
                            aal.activity_title,
                            aal.activity_description,
                            aal.ip_address,
                            'login' as event_type,
                            'Admin Login' as event_title,
                            TIMESTAMPDIFF(SECOND, aal.created_at, NOW()) as seconds_ago
                        FROM admin_activity_log aal
                        INNER JOIN admin_accounts aa ON aal.admin_id = aa.admin_id
                        WHERE aal.activity_type = 'login'
                        ORDER BY aal.created_at DESC
                        LIMIT ?";
            
            $stmt = $conn->prepare($loginSql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $description = $row['activity_description'];
                if ($row['ip_address']) {
                    $description .= ' (IP: ' . $row['ip_address'] . ')';
                }
                
                $securityEvents[] = [
                    'id' => 'login_' . $row['activity_id'],
                    'type' => 'login',
                    'icon' => 'fa-sign-in-alt',
                    'title' => $row['activity_title'],
                    'description' => $description,
                    'time' => $row['last_login'],
                    'raw_time' => $row['raw_created_at'],
                    'seconds_ago' => (int)$row['seconds_ago'],
                    'admin_name' => $row['name'],
                    'admin_email' => $row['email'],
                    'severity' => 'info'
                ];
            }
        } else {
            // No login records in admin_activity_log, use fallback
            error_log("No login records found in admin_activity_log table, using fallback to admin_accounts");
            
            $loginSql = "SELECT 
                            admin_id,
                            name,
                            email,
                            DATE_FORMAT(last_login, '%Y-%m-%d %H:%i:%s') as last_login,
                            last_login as raw_last_login,
                            'login' as event_type,
                            'Admin Login' as event_title,
                            CONCAT('Last login: ', DATE_FORMAT(last_login, '%Y-%m-%d %H:%i:%s')) as event_description,
                            TIMESTAMPDIFF(SECOND, last_login, NOW()) as seconds_ago
                        FROM admin_accounts 
                        WHERE last_login IS NOT NULL
                        ORDER BY last_login DESC
                        LIMIT ?";
            
            $stmt = $conn->prepare($loginSql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $securityEvents[] = [
                    'id' => 'login_' . $row['admin_id'] . '_' . strtotime($row['last_login']),
                    'type' => 'login',
                    'icon' => 'fa-sign-in-alt',
                    'title' => $row['event_title'],
                    'description' => $row['event_description'],
                    'time' => $row['last_login'],
                    'raw_time' => $row['raw_last_login'],
                    'seconds_ago' => (int)$row['seconds_ago'],
                    'admin_name' => $row['name'],
                    'admin_email' => $row['email'],
                    'severity' => 'info'
                ];
            }
        }
    } catch (Exception $e) {
        // If admin_activity_log table doesn't exist, fallback to admin_accounts
        error_log("Admin activity log table not found, using fallback: " . $e->getMessage());
        
        $loginSql = "SELECT 
                        admin_id,
                        name,
                        email,
                        DATE_FORMAT(last_login, '%Y-%m-%d %H:%i:%s') as last_login,
                        last_login as raw_last_login,
                        'login' as event_type,
                        'Admin Login' as event_title,
                        CONCAT('Last login: ', DATE_FORMAT(last_login, '%Y-%m-%d %H:%i:%s')) as event_description,
                        TIMESTAMPDIFF(SECOND, last_login, NOW()) as seconds_ago
                    FROM admin_accounts 
                    WHERE last_login IS NOT NULL
                    ORDER BY last_login DESC
                    LIMIT ?";
        
        $stmt = $conn->prepare($loginSql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $securityEvents[] = [
                'id' => 'login_' . $row['admin_id'] . '_' . strtotime($row['last_login']),
                'type' => 'login',
                'icon' => 'fa-sign-in-alt',
                'title' => $row['event_title'],
                'description' => $row['event_description'],
                'time' => $row['last_login'],
                'raw_time' => $row['raw_last_login'],
                'seconds_ago' => (int)$row['seconds_ago'],
                'admin_name' => $row['name'],
                'admin_email' => $row['email'],
                'severity' => 'info'
            ];
        }
    }
    
    // 2. Password Changes (from admin_activity_log table)
    try {
        // Get password changes with both current admin (performer) and target admin info
        $passwordSql = "SELECT 
                            aal.activity_id,
                            aal.admin_id as performed_by_admin_id,
                            performer.name as performed_by_name,
                            performer.email as performed_by_email,
                            DATE_FORMAT(aal.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                            aal.created_at as raw_created_at,
                            aal.activity_title,
                            aal.activity_description,
                            aal.ip_address,
                            'password_change' as event_type,
                            TIMESTAMPDIFF(SECOND, aal.created_at, NOW()) as seconds_ago
                        FROM admin_activity_log aal
                        INNER JOIN admin_accounts performer ON aal.admin_id = performer.admin_id
                        WHERE aal.activity_type = 'password_change'
                        ORDER BY aal.created_at DESC
                        LIMIT ?";
        
        $stmt = $conn->prepare($passwordSql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Extract target admin ID from description (format: "Admin ID: X, Email: Y")
            $targetAdminId = null;
            if (preg_match('/Admin ID:\s*(\d+)/', $row['activity_description'], $matches)) {
                $targetAdminId = (int)$matches[1];
            }
            
            // Get target admin info if we found the ID
            $targetAdminName = '';
            $targetAdminEmail = '';
            if ($targetAdminId) {
                $targetStmt = $conn->prepare("SELECT name, email FROM admin_accounts WHERE admin_id = ?");
                $targetStmt->bind_param("i", $targetAdminId);
                $targetStmt->execute();
                $targetResult = $targetStmt->get_result();
                if ($targetResult->num_rows > 0) {
                    $targetAdmin = $targetResult->fetch_assoc();
                    $targetAdminName = $targetAdmin['name'];
                    $targetAdminEmail = $targetAdmin['email'];
                }
                $targetStmt->close();
            }
            
            // Use target admin info if available, otherwise use performer info
            $displayName = $targetAdminName ?: $row['performed_by_name'];
            $displayEmail = $targetAdminEmail ?: $row['performed_by_email'];
            
            $description = $row['activity_description'];
            if ($row['ip_address']) {
                $description .= ' (IP: ' . $row['ip_address'] . ')';
            }
            if ($targetAdminId && $targetAdminId != $row['performed_by_admin_id']) {
                $description .= ' (Performed by: ' . $row['performed_by_name'] . ')';
            }
            
            $securityEvents[] = [
                'id' => 'pwd_' . $row['activity_id'],
                'type' => 'password_change',
                'icon' => 'fa-lock',
                'title' => 'Password Changed - ' . $displayName,
                'description' => $description,
                'time' => $row['created_at'],
                'raw_time' => $row['raw_created_at'],
                'seconds_ago' => (int)$row['seconds_ago'],
                'admin_name' => $displayName,
                'admin_email' => $displayEmail,
                'severity' => 'warning'
            ];
        }
    } catch (Exception $e) {
        error_log("Error fetching password changes from admin_activity_log: " . $e->getMessage());
    }
    
    // 3. Email Changes (from admin_activity_log table)
    try {
        // Get email changes with both current admin (performer) and target admin info
        $emailSql = "SELECT 
                        aal.activity_id,
                        aal.admin_id as performed_by_admin_id,
                        performer.name as performed_by_name,
                        performer.email as performed_by_email,
                        DATE_FORMAT(aal.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                        aal.created_at as raw_created_at,
                        aal.activity_title,
                        aal.activity_description,
                        aal.ip_address,
                        'email_change' as event_type,
                        TIMESTAMPDIFF(SECOND, aal.created_at, NOW()) as seconds_ago
                    FROM admin_activity_log aal
                    INNER JOIN admin_accounts performer ON aal.admin_id = performer.admin_id
                    WHERE aal.activity_type = 'email_change'
                    ORDER BY aal.created_at DESC
                    LIMIT ?";
        
        $stmt = $conn->prepare($emailSql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Extract target admin ID from description (format: "Admin ID: X, Old Email: Y, New Email: Z")
            $targetAdminId = null;
            if (preg_match('/Admin ID:\s*(\d+)/', $row['activity_description'], $matches)) {
                $targetAdminId = (int)$matches[1];
            }
            
            // Get target admin info if we found the ID
            $targetAdminName = '';
            $targetAdminEmail = '';
            if ($targetAdminId) {
                $targetStmt = $conn->prepare("SELECT name, email FROM admin_accounts WHERE admin_id = ?");
                $targetStmt->bind_param("i", $targetAdminId);
                $targetStmt->execute();
                $targetResult = $targetStmt->get_result();
                if ($targetResult->num_rows > 0) {
                    $targetAdmin = $targetResult->fetch_assoc();
                    $targetAdminName = $targetAdmin['name'];
                    $targetAdminEmail = $targetAdmin['email'];
                }
                $targetStmt->close();
            }
            
            // Use target admin info if available, otherwise use performer info
            $displayName = $targetAdminName ?: $row['performed_by_name'];
            $displayEmail = $targetAdminEmail ?: $row['performed_by_email'];
            
            $description = $row['activity_description'];
            if ($row['ip_address']) {
                $description .= ' (IP: ' . $row['ip_address'] . ')';
            }
            if ($targetAdminId && $targetAdminId != $row['performed_by_admin_id']) {
                $description .= ' (Performed by: ' . $row['performed_by_name'] . ')';
            }
            
            $securityEvents[] = [
                'id' => 'email_' . $row['activity_id'],
                'type' => 'email_change',
                'icon' => 'fa-envelope',
                'title' => 'Email Changed - ' . $displayName,
                'description' => $description,
                'time' => $row['created_at'],
                'raw_time' => $row['raw_created_at'],
                'seconds_ago' => (int)$row['seconds_ago'],
                'admin_name' => $displayName,
                'admin_email' => $displayEmail,
                'severity' => 'warning'
            ];
        }
    } catch (Exception $e) {
        error_log("Error fetching email changes from admin_activity_log: " . $e->getMessage());
    }
    
    // 4. Account Status Changes (from admin_activity_log table)
    try {
        // Get status changes with both current admin (performer) and target admin info
        $statusSql = "SELECT 
                        aal.activity_id,
                        aal.admin_id as performed_by_admin_id,
                        performer.name as performed_by_name,
                        performer.email as performed_by_email,
                        DATE_FORMAT(aal.created_at, '%Y-%m-%d %H:%i:%s') as updated_at,
                        aal.created_at as raw_created_at,
                        aal.activity_title,
                        aal.activity_description,
                        aal.ip_address,
                        'status_change' as event_type,
                        TIMESTAMPDIFF(SECOND, aal.created_at, NOW()) as seconds_ago
                    FROM admin_activity_log aal
                    INNER JOIN admin_accounts performer ON aal.admin_id = performer.admin_id
                    WHERE aal.activity_type = 'status_change'
                    ORDER BY aal.created_at DESC
                    LIMIT ?";
        
        $stmt = $conn->prepare($statusSql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Extract target admin ID from description (format: "Admin account activated: Admin ID X, Name: Y, Email: Z")
            $targetAdminId = null;
            // Try multiple patterns to extract admin ID
            if (preg_match('/Admin ID[:\s]+(\d+)/', $row['activity_description'], $matches)) {
                $targetAdminId = (int)$matches[1];
            } elseif (preg_match('/admin_id\s+(\d+)/i', $row['activity_description'], $matches)) {
                $targetAdminId = (int)$matches[1];
            }
            
            // Extract status from title or description
            $status = 'active';
            if (stripos($row['activity_title'], 'inactive') !== false || stripos($row['activity_description'], 'deactivated') !== false) {
                $status = 'inactive';
            } elseif (stripos($row['activity_title'], 'active') !== false || stripos($row['activity_description'], 'activated') !== false) {
                $status = 'active';
            }
            
            // Get target admin info if we found the ID
            $targetAdminName = '';
            $targetAdminEmail = '';
            if ($targetAdminId) {
                $targetStmt = $conn->prepare("SELECT name, email, status FROM admin_accounts WHERE admin_id = ?");
                $targetStmt->bind_param("i", $targetAdminId);
                $targetStmt->execute();
                $targetResult = $targetStmt->get_result();
                if ($targetResult->num_rows > 0) {
                    $targetAdmin = $targetResult->fetch_assoc();
                    $targetAdminName = $targetAdmin['name'];
                    $targetAdminEmail = $targetAdmin['email'];
                    $status = $targetAdmin['status']; // Use current status from database
                }
                $targetStmt->close();
            }
            
            // Use target admin info if available, otherwise use performer info
            $displayName = $targetAdminName ?: $row['performed_by_name'];
            $displayEmail = $targetAdminEmail ?: $row['performed_by_email'];
            
            $description = $row['activity_description'];
            if ($row['ip_address']) {
                $description .= ' (IP: ' . $row['ip_address'] . ')';
            }
            if ($targetAdminId && $targetAdminId != $row['performed_by_admin_id']) {
                $description .= ' (Performed by: ' . $row['performed_by_name'] . ')';
            }
            
            $securityEvents[] = [
                'id' => 'status_' . $row['activity_id'],
                'type' => 'status_change',
                'icon' => $status === 'active' ? 'fa-check-circle' : 'fa-ban',
                'title' => $row['activity_title'],
                'description' => $description,
                'time' => $row['updated_at'],
                'raw_time' => $row['raw_created_at'],
                'seconds_ago' => (int)$row['seconds_ago'],
                'admin_name' => $displayName,
                'admin_email' => $displayEmail,
                'severity' => $status === 'active' ? 'info' : 'danger'
            ];
        }
    } catch (Exception $e) {
        // If admin_activity_log table doesn't exist, fallback to admin_accounts
        error_log("Error fetching status changes from admin_activity_log: " . $e->getMessage());
        
        $statusSql = "SELECT 
                        admin_id,
                        name,
                        email,
                        status,
                        DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') as updated_at,
                        updated_at as raw_updated_at,
                        'status_change' as event_type,
                        CONCAT('Account Status: ', UPPER(status)) as event_title,
                        CONCAT('Admin account status changed to ', UPPER(status)) as event_description,
                        TIMESTAMPDIFF(SECOND, updated_at, NOW()) as seconds_ago
                    FROM admin_accounts
                    WHERE updated_at != created_at
                    ORDER BY updated_at DESC
                    LIMIT ?";
        
        $stmt = $conn->prepare($statusSql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $securityEvents[] = [
                'id' => 'status_' . $row['admin_id'] . '_' . strtotime($row['updated_at']),
                'type' => 'status_change',
                'icon' => $row['status'] === 'active' ? 'fa-check-circle' : 'fa-ban',
                'title' => $row['event_title'],
                'description' => $row['event_description'],
                'time' => $row['updated_at'],
                'raw_time' => $row['raw_updated_at'],
                'seconds_ago' => (int)$row['seconds_ago'],
                'admin_name' => $row['name'],
                'admin_email' => $row['email'],
                'severity' => $row['status'] === 'active' ? 'info' : 'danger'
            ];
        }
    }
    
    // Filter out events with invalid or empty time
    $securityEvents = array_filter($securityEvents, function($event) {
        return !empty($event['time']) && $event['time'] !== '0000-00-00 00:00:00' && $event['time'] !== null;
    });
    
    // Sort all events by time (newest first)
    usort($securityEvents, function($a, $b) {
        $timeA = strtotime($a['time']);
        $timeB = strtotime($b['time']);
        
        // Handle invalid timestamps
        if ($timeA === false) $timeA = 0;
        if ($timeB === false) $timeB = 0;
        
        return $timeB - $timeA;
    });
    
    // Limit total results
    $securityEvents = array_slice($securityEvents, 0, $limit);
    
    // Format time ago for each event
    foreach ($securityEvents as &$event) {
        if (!empty($event['time']) && $event['time'] !== '0000-00-00 00:00:00') {
            // If seconds_ago is already calculated, use it for more accurate time
            if (isset($event['seconds_ago']) && $event['seconds_ago'] >= 0) {
                $secondsAgo = (int)$event['seconds_ago'];
                
                if ($secondsAgo < 60) {
                    $event['time_ago'] = 'Just now';
                } elseif ($secondsAgo < 3600) {
                    $minutes = floor($secondsAgo / 60);
                    $event['time_ago'] = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
                } elseif ($secondsAgo < 86400) {
                    $hours = floor($secondsAgo / 3600);
                    $event['time_ago'] = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                } elseif ($secondsAgo < 2592000) {
                    $days = floor($secondsAgo / 86400);
                    $event['time_ago'] = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                } elseif ($secondsAgo < 31536000) {
                    $months = floor($secondsAgo / 2592000);
                    $event['time_ago'] = $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
                } else {
                    $years = floor($secondsAgo / 31536000);
                    $event['time_ago'] = $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
                }
            } else {
                // Fallback to getTimeAgo function
                $event['time_ago'] = getTimeAgo($event['time']);
            }
        } else {
            $event['time_ago'] = 'Unknown time';
        }
    }
    
    // Get security statistics from admin_activity_log table directly
    $stats = [
        'total_logins' => 0,
        'recent_logins' => 0,
        'password_changes' => 0,
        'email_changes' => 0,
        'status_changes' => 0
    ];
    
    try {
        // Count logins
        $loginCountSql = "SELECT COUNT(*) as count FROM admin_activity_log WHERE activity_type = 'login'";
        $result = $conn->query($loginCountSql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_logins'] = (int)$row['count'];
        }
        
        // Count recent logins (last 7 days)
        $recentLoginSql = "SELECT COUNT(*) as count FROM admin_activity_log 
                          WHERE activity_type = 'login' 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $result = $conn->query($recentLoginSql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['recent_logins'] = (int)$row['count'];
        }
        
        // Count password changes
        $passwordCountSql = "SELECT COUNT(*) as count FROM admin_activity_log WHERE activity_type = 'password_change'";
        $result = $conn->query($passwordCountSql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['password_changes'] = (int)$row['count'];
        }
        
        // Count email changes
        $emailCountSql = "SELECT COUNT(*) as count FROM admin_activity_log WHERE activity_type = 'email_change'";
        $result = $conn->query($emailCountSql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['email_changes'] = (int)$row['count'];
        }
        
        // Count status changes
        $statusCountSql = "SELECT COUNT(*) as count FROM admin_activity_log WHERE activity_type = 'status_change'";
        $result = $conn->query($statusCountSql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['status_changes'] = (int)$row['count'];
        }
    } catch (Exception $e) {
        error_log("Error calculating security statistics: " . $e->getMessage());
        // Fallback to counting from events array
        foreach ($securityEvents as $event) {
            if ($event['type'] === 'login') {
                $stats['total_logins']++;
                $eventTime = strtotime($event['time']);
                if ($eventTime !== false && $eventTime > strtotime('-7 days')) {
                    $stats['recent_logins']++;
                }
            } elseif ($event['type'] === 'password_change') {
                $stats['password_changes']++;
            } elseif ($event['type'] === 'email_change') {
                $stats['email_changes']++;
            } elseif ($event['type'] === 'status_change') {
                $stats['status_changes']++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'events' => $securityEvents,
        'stats' => $stats,
        'total' => count($securityEvents)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching security events: ' . $e->getMessage()
    ]);
}

function getTimeAgo($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === null) {
        return 'Unknown time';
    }
    
    // Handle different datetime formats
    $timestamp = false;
    
    // Try parsing as MySQL datetime format
    if (is_string($datetime)) {
        // Remove any extra whitespace
        $datetime = trim($datetime);
        
        // Try strtotime first
        $timestamp = @strtotime($datetime);
        
        // If strtotime failed, try DateTime with timezone
        if ($timestamp === false) {
            try {
                $date = new DateTime($datetime, new DateTimeZone('Asia/Manila'));
                $timestamp = $date->getTimestamp();
            } catch (Exception $e) {
                // Try without timezone
                try {
                    $date = new DateTime($datetime);
                    $date->setTimezone(new DateTimeZone('Asia/Manila'));
                    $timestamp = $date->getTimestamp();
                } catch (Exception $e2) {
                    error_log("Error parsing datetime in getTimeAgo: " . $e2->getMessage() . " - Date: " . var_export($datetime, true));
                    return 'Invalid time';
                }
            }
        }
    } elseif (is_numeric($datetime)) {
        $timestamp = (int)$datetime;
    }
    
    // If still false, return error
    if ($timestamp === false || $timestamp <= 0) {
        error_log("Failed to parse datetime: " . var_export($datetime, true));
        return 'Invalid time';
    }
    
    // Get current time with timezone
    $current = time();
    $diff = $current - $timestamp;
    
    // Debug logging for time calculation (only for recent events to avoid spam)
    if (abs($diff) < 300) { // Only log for events within 5 minutes
        error_log("Time calculation: datetime=$datetime, timestamp=$timestamp, current=$current, diff=$diff seconds");
    }
    
    // Handle negative differences (future dates)
    if ($diff < 0) {
        error_log("Warning: Future date detected in getTimeAgo. datetime=$datetime, timestamp=$timestamp, current=$current, diff=$diff");
        // If it's in the future by less than 5 minutes, assume it's a timezone issue and show "Just now"
        if ($diff > -300) {
            return 'Just now';
        }
        return 'Invalid time';
    }
    
    // Calculate time ago
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}
?>

