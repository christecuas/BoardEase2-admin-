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
    $filter = $_GET['filter'] ?? 'all';
    $date = $_GET['date'] ?? null;
    $limit = intval($_GET['limit'] ?? 50);
    
    $activities = [];
    
    // 1. Admin Login Activities (from admin_activity_log table)
    if ($filter === 'all' || $filter === 'login') {
        // First, try to get from admin_activity_log table
        try {
            $loginSql = "SELECT 
                            aal.activity_id,
                            aal.admin_id,
                            aa.name as admin_name,
                            aa.email as admin_email,
                            DATE_FORMAT(aal.created_at, '%Y-%m-%d %H:%i:%s') as activity_time,
                            aal.created_at as raw_created_at,
                            aal.activity_type,
                            aal.activity_title,
                            aal.activity_description,
                            aal.ip_address,
                            TIMESTAMPDIFF(SECOND, aal.created_at, NOW()) as seconds_ago
                        FROM admin_activity_log aal
                        INNER JOIN admin_accounts aa ON aal.admin_id = aa.admin_id
                        WHERE aal.activity_type = 'login'";
            
            if ($date) {
                $loginSql .= " AND DATE(aal.created_at) = ?";
            }
            
            $loginSql .= " ORDER BY aal.created_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($loginSql);
            if ($date) {
                $stmt->bind_param("si", $date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $description = $row['activity_description'];
                if ($row['ip_address']) {
                    $description .= ' (IP: ' . $row['ip_address'] . ')';
                }
                
                $activities[] = [
                    'id' => 'login_' . $row['activity_id'],
                    'type' => 'login',
                    'icon' => 'fa-sign-in-alt',
                    'title' => $row['activity_title'],
                    'description' => $description,
                    'time' => $row['activity_time'],
                    'raw_time' => $row['raw_created_at'],
                    'seconds_ago' => (int)$row['seconds_ago'],
                    'admin_name' => $row['admin_name'],
                    'admin_email' => $row['admin_email']
                ];
            }
        } catch (Exception $e) {
            // If admin_activity_log table doesn't exist, fallback to admin_accounts
            error_log("Admin activity log table not found, using fallback: " . $e->getMessage());
            
            $loginSql = "SELECT 
                            admin_id,
                            name as admin_name,
                            email as admin_email,
                            DATE_FORMAT(last_login, '%Y-%m-%d %H:%i:%s') as activity_time,
                            last_login as raw_last_login,
                            'login' as activity_type,
                            CONCAT(name, ' logged in') as activity_title,
                            CONCAT('Admin login successful') as activity_description,
                            TIMESTAMPDIFF(SECOND, last_login, NOW()) as seconds_ago
                        FROM admin_accounts 
                        WHERE last_login IS NOT NULL";
            
            if ($date) {
                $loginSql .= " AND DATE(last_login) = ?";
            }
            
            $loginSql .= " ORDER BY last_login DESC LIMIT ?";
            
            $stmt = $conn->prepare($loginSql);
            if ($date) {
                $stmt->bind_param("si", $date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = [
                    'id' => 'login_' . $row['admin_id'] . '_' . strtotime($row['activity_time']),
                    'type' => 'login',
                    'icon' => 'fa-sign-in-alt',
                    'title' => $row['activity_title'],
                    'description' => $row['activity_description'],
                    'time' => $row['activity_time'],
                    'raw_time' => $row['raw_last_login'],
                    'seconds_ago' => isset($row['seconds_ago']) ? (int)$row['seconds_ago'] : null,
                    'admin_name' => $row['admin_name'],
                    'admin_email' => $row['admin_email']
                ];
            }
        }
    }
    
    // 2. User Management Activities (Registration Approvals/Rejections)
    if ($filter === 'all' || $filter === 'user') {
        // First, try to get from admin_activity_log table
        try {
            $userSql = "SELECT 
                            aal.activity_id,
                            aal.activity_type,
                            aal.activity_title,
                            aal.activity_description,
                            DATE_FORMAT(aal.created_at, '%Y-%m-%d %H:%i:%s') as activity_time,
                            aal.created_at as raw_created_at,
                            aa.name as admin_name,
                            aa.email as admin_email,
                            TIMESTAMPDIFF(SECOND, aal.created_at, NOW()) as seconds_ago
                        FROM admin_activity_log aal
                        INNER JOIN admin_accounts aa ON aal.admin_id = aa.admin_id
                        WHERE aal.activity_type IN ('user_approved', 'user_rejected', 'user_created', 'user_updated', 'user_deleted')";
            
            if ($date) {
                $userSql .= " AND DATE(aal.created_at) = ?";
            }
            
            $userSql .= " ORDER BY aal.created_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($userSql);
            if ($date) {
                $stmt->bind_param("si", $date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $icon = $row['activity_type'] === 'user_approved' ? 'fa-user-check' : 
                       ($row['activity_type'] === 'user_rejected' ? 'fa-user-times' : 
                       ($row['activity_type'] === 'user_created' ? 'fa-user-plus' : 
                       ($row['activity_type'] === 'user_deleted' ? 'fa-user-minus' : 'fa-user-edit')));
                
                $activities[] = [
                    'id' => 'user_' . $row['activity_id'],
                    'type' => 'user',
                    'icon' => $icon,
                    'title' => $row['activity_title'],
                    'description' => $row['activity_description'],
                    'time' => $row['activity_time'],
                    'raw_time' => $row['raw_created_at'],
                    'seconds_ago' => (int)$row['seconds_ago'],
                    'admin_name' => $row['admin_name'],
                    'admin_email' => $row['admin_email']
                ];
            }
        } catch (Exception $e) {
            // If admin_activity_log table doesn't exist, fallback to registrations table
            error_log("Admin activity log table not found, using fallback: " . $e->getMessage());
            
            $userSql = "SELECT 
                            r.id,
                            r.status,
                            CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), ' ', COALESCE(r.suffix, '')) as user_name,
                            r.email,
                            r.role,
                            DATE_FORMAT(r.updated_at, '%Y-%m-%d %H:%i:%s') as activity_time,
                            r.updated_at as raw_updated_at,
                            CASE 
                                WHEN r.status = 'approved' THEN 'user_approved'
                                WHEN r.status = 'rejected' THEN 'user_rejected'
                                ELSE 'user_updated'
                            END as activity_type,
                            CASE 
                                WHEN r.status = 'approved' THEN CONCAT('User registration approved: ', COALESCE(r.first_name, ''), ' ', COALESCE(r.last_name, ''))
                                WHEN r.status = 'rejected' THEN CONCAT('User registration rejected: ', COALESCE(r.first_name, ''), ' ', COALESCE(r.last_name, ''))
                                ELSE CONCAT('User status updated: ', COALESCE(r.first_name, ''), ' ', COALESCE(r.last_name, ''))
                            END as activity_title,
                            CONCAT('Registration ', r.status, ' for ', r.role, ' account') as activity_description,
                            TIMESTAMPDIFF(SECOND, r.updated_at, NOW()) as seconds_ago
                        FROM registrations r
                        WHERE r.status IN ('approved', 'rejected') AND r.updated_at IS NOT NULL";
            
            if ($date) {
                $userSql .= " AND DATE(r.updated_at) = ?";
            }
            
            $userSql .= " ORDER BY r.updated_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($userSql);
            if ($date) {
                $stmt->bind_param("si", $date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $icon = $row['activity_type'] === 'user_approved' ? 'fa-user-check' : 
                       ($row['activity_type'] === 'user_rejected' ? 'fa-user-times' : 'fa-user-edit');
                
                $activities[] = [
                    'id' => 'user_' . $row['id'] . '_' . strtotime($row['activity_time']),
                    'type' => 'user',
                    'icon' => $icon,
                    'title' => $row['activity_title'],
                    'description' => $row['activity_description'],
                    'time' => $row['activity_time'],
                    'raw_time' => $row['raw_updated_at'],
                    'seconds_ago' => isset($row['seconds_ago']) ? (int)$row['seconds_ago'] : null,
                    'user_name' => trim($row['user_name']),
                    'user_email' => $row['email'],
                    'user_role' => $row['role']
                ];
            }
        }
    }
    
    // 3. System Activities (from various system events)
    if ($filter === 'all' || $filter === 'system') {
        // Get new boarding houses
        try {
            $bhSql = "SELECT 
                        bh.bh_id as event_id,
                        'system' as activity_type,
                        'fa-home' as icon,
                        'New Boarding House Added' as activity_title,
                        CONCAT('New boarding house: ', bh.bh_name, ' added') as activity_description,
                        DATE_FORMAT(bh.created_at, '%Y-%m-%d %H:%i:%s') as activity_time
                    FROM boarding_houses bh
                    WHERE 1=1";
            
            if ($date) {
                $bhSql .= " AND DATE(bh.created_at) = ?";
            }
            
            $bhSql .= " ORDER BY bh.created_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($bhSql);
            if ($date) {
                $stmt->bind_param("si", $date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = [
                    'id' => 'bh_' . $row['event_id'],
                    'type' => 'system',
                    'icon' => $row['icon'],
                    'title' => $row['activity_title'],
                    'description' => $row['activity_description'],
                    'time' => $row['activity_time']
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching boarding houses for activity log: " . $e->getMessage());
        }
        
        // Get new bookings as system activity
        try {
            $bookingSql = "SELECT 
                            b.booking_id as event_id,
                            'system' as activity_type,
                            'fa-calendar-plus' as icon,
                            'New Booking Created' as activity_title,
                            CONCAT('New booking created for room') as activity_description,
                            DATE_FORMAT(b.booking_date, '%Y-%m-%d %H:%i:%s') as activity_time
                        FROM bookings b
                        WHERE 1=1";
            
            if ($date) {
                $bookingSql .= " AND DATE(b.booking_date) = ?";
            }
            
            $bookingSql .= " ORDER BY b.booking_date DESC LIMIT ?";
            
            $stmt = $conn->prepare($bookingSql);
            if ($date) {
                $stmt->bind_param("si", $date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $activities[] = [
                    'id' => 'booking_' . $row['event_id'],
                    'type' => 'system',
                    'icon' => $row['icon'],
                    'title' => $row['activity_title'],
                    'description' => $row['activity_description'],
                    'time' => $row['activity_time']
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching bookings for activity log: " . $e->getMessage());
        }
    }
    
    // Filter out activities with invalid or empty time
    $activities = array_filter($activities, function($activity) {
        return !empty($activity['time']) && $activity['time'] !== '0000-00-00 00:00:00' && $activity['time'] !== null;
    });
    
    // Sort all activities by time (newest first)
    usort($activities, function($a, $b) {
        $timeA = strtotime($a['time']);
        $timeB = strtotime($b['time']);
        
        // Handle invalid timestamps
        if ($timeA === false) $timeA = 0;
        if ($timeB === false) $timeB = 0;
        
        return $timeB - $timeA;
    });
    
    // Limit total results
    $activities = array_slice($activities, 0, $limit);
    
    // Format time ago for each activity
    foreach ($activities as &$activity) {
        // Ensure time is in correct format
        if (!empty($activity['time']) && $activity['time'] !== '0000-00-00 00:00:00') {
            // If seconds_ago is already calculated, use it for more accurate time
            if (isset($activity['seconds_ago']) && $activity['seconds_ago'] >= 0) {
                $secondsAgo = (int)$activity['seconds_ago'];
                
                if ($secondsAgo < 60) {
                    $activity['time_ago'] = 'Just now';
                } elseif ($secondsAgo < 3600) {
                    $minutes = floor($secondsAgo / 60);
                    $activity['time_ago'] = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
                } elseif ($secondsAgo < 86400) {
                    $hours = floor($secondsAgo / 3600);
                    $activity['time_ago'] = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                } elseif ($secondsAgo < 2592000) {
                    $days = floor($secondsAgo / 86400);
                    $activity['time_ago'] = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                } elseif ($secondsAgo < 31536000) {
                    $months = floor($secondsAgo / 2592000);
                    $activity['time_ago'] = $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
                } else {
                    $years = floor($secondsAgo / 31536000);
                    $activity['time_ago'] = $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
                }
            } else {
                // Fallback to getTimeAgo function
                $activity['time_ago'] = getTimeAgo($activity['time']);
            }
            // Also store raw time for debugging
            if (!isset($activity['raw_time'])) {
                $activity['raw_time'] = $activity['time'];
            }
        } else {
            $activity['time_ago'] = 'Unknown time';
            $activity['raw_time'] = $activity['time'] ?? null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'activities' => array_values($activities), // Re-index array
        'total' => count($activities)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching activity logs: ' . $e->getMessage()
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

