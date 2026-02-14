<?php
// Admin System Notifications Helper
// Creates and manages system-level notifications visible to admins in the dashboard
require_once 'db_helper.php';

class AdminSystemNotifications {
    
    /**
     * Get system notifications for admin dashboard
     * Shows important system events like new registrations, new boarding houses, disputes, etc.
     */
    public static function getSystemNotifications($limit = 1000, $offset = 0) {
        try {
            $db = getDB();
            $notifications = [];
            
            // 1. New User Registrations (pending approvals)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        r.id as event_id,
                        'user_registration' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, 
                               CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as user_name,
                        r.email,
                        r.role,
                        r.created_at as event_time,
                        'New User Registration' as title,
                        CONCAT(r.first_name, ' ', r.last_name, ' registered as a ', COALESCE(r.role, 'user')) as message,
                        'pending_admin_review' as status,
                        '#28a745' as icon_color,
                        'user-plus' as icon_name
                    FROM registrations r
                    WHERE r.status = 'pending_admin_review'
                    ORDER BY r.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute();
                $registrations = $stmt->fetchAll();
                foreach ($registrations as $reg) {
                    $notifications[] = $reg;
                }
            } catch (Exception $e) {
                error_log("Pending registrations query error: " . $e->getMessage());
            }
            
            // 2. New Boarding Houses (recently added)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        bh.bh_id as event_id,
                        'boarding_house_added' as event_type,
                        bh.bh_name,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as owner_name,
                        r.email as owner_email,
                        r.role,
                        bh.created_at as event_time,
                        'New Boarding House Added' as title,
                        CONCAT(bh.bh_name, ' was registered by ', CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name)) as message,
                        'active' as status,
                        '#007bff' as icon_color,
                        'home' as icon_name
                    FROM boarding_houses bh
                    JOIN users u ON bh.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    ORDER BY bh.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute();
                $boardingHouses = $stmt->fetchAll();
                foreach ($boardingHouses as $bh) {
                    $notifications[] = $bh;
                }
            } catch (Exception $e) {
                error_log("Boarding houses query error: " . $e->getMessage());
            }
            
            // 3. Payment Issues/Disputes (if disputes table exists)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        d.dispute_id as event_id,
                        'payment_dispute' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        d.dispute_type,
                        d.status,
                        d.created_at as event_time,
                        'Payment Issue Reported' as title,
                        CONCAT('Payment dispute reported: ', d.description) as message,
                        d.status as status,
                        '#ffc107' as icon_color,
                        'exclamation-triangle' as icon_name
                    FROM disputes d
                    JOIN users u ON d.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE d.status IN ('pending', 'open')
                    ORDER BY d.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute();
                $disputes = $stmt->fetchAll();
                foreach ($disputes as $dispute) {
                    $notifications[] = $dispute;
                }
            } catch (Exception $e) {
                // Disputes table might not exist, skip
                error_log("Disputes table not found: " . $e->getMessage());
            }
            
            // 4. Recent Large Payments (over ₱10,000)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        p.payment_id as event_id,
                        'large_payment' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        p.payment_amount,
                        p.payment_status,
                        p.payment_date as event_time,
                        'Large Payment Completed' as title,
                        CONCAT('Payment of ₱', FORMAT(p.payment_amount, 2), ' received from ', CONCAT(r.first_name, ' ', r.last_name)) as message,
                        p.payment_status as status,
                        '#6f42c1' as icon_color,
                        'credit-card' as icon_name
                    FROM payments p
                    JOIN users u ON p.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE p.payment_amount >= 10000 
                    AND p.payment_status = 'Confirmed'
                    ORDER BY p.payment_date DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $payments = $stmt->fetchAll();
                foreach ($payments as $payment) {
                    $notifications[] = $payment;
                }
            } catch (Exception $e) {
                error_log("Large payments query error: " . $e->getMessage());
            }
            
            // 5. Maintenance Requests Completed (recent)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        mr.maintenance_id as event_id,
                        'maintenance_completed' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        mr.title,
                        mr.status,
                        mr.updated_at as event_time,
                        'Maintenance Request Completed' as title,
                        CONCAT(mr.title, ' has been resolved') as message,
                        mr.status as status,
                        '#20c997' as icon_color,
                        'check-circle' as icon_name
                    FROM maintenance_requests mr
                    JOIN users u ON mr.boarder_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE mr.status = 'Completed'
                    ORDER BY mr.updated_at DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $maintenance = $stmt->fetchAll();
                foreach ($maintenance as $maint) {
                    $notifications[] = $maint;
                }
            } catch (Exception $e) {
                error_log("Maintenance completed query error: " . $e->getMessage());
            }
            
            // 6. Flagged/Suspended/Inactive Accounts
            try {
                $stmt = $db->prepare("
                    SELECT 
                        u.user_id as event_id,
                        'account_status_change' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        r.role,
                        u.status,
                        u.updated_at as event_time,
                        CASE 
                            WHEN u.status = 'Flagged' THEN 'Account Flagged'
                            WHEN u.status = 'Suspended' THEN 'Account Suspended'
                            WHEN u.status = 'Inactive' THEN 'Account Deactivated'
                            ELSE 'Account Status Changed'
                        END as title,
                        CONCAT('User ', r.email, ' status changed to: ', u.status) as message,
                        u.status as status,
                        '#dc3545' as icon_color,
                        'flag' as icon_name
                    FROM users u
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE u.status IN ('Flagged', 'Suspended', 'Inactive')
                    ORDER BY u.updated_at DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $flagged = $stmt->fetchAll();
                foreach ($flagged as $flag) {
                    $notifications[] = $flag;
                }
            } catch (Exception $e) {
                error_log("Flagged users query error: " . $e->getMessage());
            }
            
            // 7. New Bookings Created (recent)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        b.booking_id as event_id,
                        'new_booking' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        r.role,
                        bhr.room_name,
                        bh.bh_name,
                        b.booking_status,
                        b.booking_date as event_time,
                        'New Booking Created' as title,
                        CONCAT(CONCAT(r.first_name, ' ', r.last_name), ' created a booking for ', bhr.room_name, ' at ', bh.bh_name) as message,
                        b.booking_status as status,
                        '#17a2b8' as icon_color,
                        'calendar-plus' as icon_name
                    FROM bookings b
                    JOIN users u ON b.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    JOIN room_units ru ON b.room_id = ru.room_id
                    JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                    JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                    ORDER BY b.booking_date DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $bookings = $stmt->fetchAll();
                foreach ($bookings as $booking) {
                    $notifications[] = $booking;
                }
            } catch (Exception $e) {
                error_log("Bookings query error: " . $e->getMessage());
            }
            
            // 8. Booking Status Changes (approved/rejected)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        b.booking_id as event_id,
                        'booking_status_change' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        bhr.room_name,
                        b.booking_status,
                        b.updated_at as event_time,
                        CASE 
                            WHEN b.booking_status = 'Confirmed' THEN 'Booking Approved'
                            WHEN b.booking_status = 'Cancelled' THEN 'Booking Cancelled'
                            ELSE 'Booking Status Changed'
                        END as title,
                        CONCAT('Booking for ', bhr.room_name, ' status changed to: ', b.booking_status) as message,
                        b.booking_status as status,
                        CASE 
                            WHEN b.booking_status = 'Confirmed' THEN '#28a745'
                            WHEN b.booking_status = 'Cancelled' THEN '#dc3545'
                            ELSE '#ffc107'
                        END as icon_color,
                        CASE 
                            WHEN b.booking_status = 'Confirmed' THEN 'check-circle'
                            WHEN b.booking_status = 'Cancelled' THEN 'times-circle'
                            ELSE 'exclamation-circle'
                        END as icon_name
                    FROM bookings b
                    JOIN users u ON b.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    JOIN room_units ru ON b.room_id = ru.room_id
                    JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                    WHERE b.booking_status IN ('Confirmed', 'Cancelled')
                    AND (b.updated_at != b.booking_date OR b.updated_at IS NOT NULL)
                    ORDER BY b.updated_at DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $bookingChanges = $stmt->fetchAll();
                foreach ($bookingChanges as $change) {
                    $notifications[] = $change;
                }
            } catch (Exception $e) {
                error_log("Booking status changes query error: " . $e->getMessage());
            }
            
            // 9. Overdue Payments (critical - show all overdue, not just recent)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        p.payment_id as event_id,
                        'payment_overdue' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        p.payment_amount,
                        p.payment_status,
                        p.payment_date as event_time,
                        'Payment Overdue' as title,
                        CONCAT('Payment of ₱', FORMAT(p.payment_amount, 2), ' from ', CONCAT(r.first_name, ' ', r.last_name), ' is overdue') as message,
                        'overdue' as status,
                        '#dc3545' as icon_color,
                        'exclamation-triangle' as icon_name
                    FROM payments p
                    JOIN users u ON p.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE p.payment_status = 'Overdue'
                    ORDER BY p.payment_date DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $overduePayments = $stmt->fetchAll();
                foreach ($overduePayments as $payment) {
                    $notifications[] = $payment;
                }
            } catch (Exception $e) {
                error_log("Overdue payments query error: " . $e->getMessage());
            }
            
            // 10. New Reviews (recent)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        rev.review_id as event_id,
                        'new_review' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        bh.bh_name,
                        rev.overall_rating,
                        rev.created_at as event_time,
                        'New Review Submitted' as title,
                        CONCAT(CONCAT(r.first_name, ' ', r.last_name), ' left a ', rev.overall_rating, '-star review for ', bh.bh_name) as message,
                        'published' as status,
                        '#ffc107' as icon_color,
                        'star' as icon_name
                    FROM reviews rev
                    JOIN users u ON rev.boarder_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    JOIN boarding_houses bh ON rev.boarding_house_id = bh.bh_id
                    ORDER BY rev.created_at DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $reviews = $stmt->fetchAll();
                foreach ($reviews as $review) {
                    $notifications[] = $review;
                }
            } catch (Exception $e) {
                error_log("Reviews query error: " . $e->getMessage());
            }
            
            // 11. Account Status Changes (approved/rejected registrations) - Show ALL historical approvals/rejections
            try {
                $stmt = $db->prepare("
                    SELECT 
                        r.id as event_id,
                        'account_status_change' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        r.role,
                        r.status,
                        COALESCE(r.updated_at, r.created_at) as event_time,
                        CASE 
                            WHEN r.status = 'approved' THEN 'Account Approved'
                            WHEN r.status = 'rejected' THEN 'Account Rejected'
                            ELSE 'Account Status Changed'
                        END as title,
                        CONCAT(CONCAT(r.first_name, ' ', r.last_name), ' account status changed to: ', UPPER(r.status)) as message,
                        r.status as status,
                        CASE 
                            WHEN r.status = 'approved' THEN '#28a745'
                            WHEN r.status = 'rejected' THEN '#dc3545'
                            ELSE '#ffc107'
                        END as icon_color,
                        CASE 
                            WHEN r.status = 'approved' THEN 'check-circle'
                            WHEN r.status = 'rejected' THEN 'times-circle'
                            ELSE 'info-circle'
                        END as icon_name
                    FROM registrations r
                    WHERE r.status IN ('approved', 'rejected')
                    ORDER BY COALESCE(r.updated_at, r.created_at) DESC
                    LIMIT 2000
                ");
                $stmt->execute();
                $accountChanges = $stmt->fetchAll();
                foreach ($accountChanges as $change) {
                    $notifications[] = $change;
                }
            } catch (Exception $e) {
                error_log("Account status changes query error: " . $e->getMessage());
            }
            
            // 12. New Active Boarders (recently became active)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        ab.active_id as event_id,
                        'new_active_boarder' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        bh.bh_name,
                        ab.start_date as event_time,
                        'New Active Boarder' as title,
                        CONCAT(CONCAT(r.first_name, ' ', r.last_name), ' became an active boarder at ', bh.bh_name) as message,
                        'active' as status,
                        '#20c997' as icon_color,
                        'user-check' as icon_name
                    FROM active_boarders ab
                    JOIN users u ON ab.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    JOIN boarding_houses bh ON ab.bh_id = bh.bh_id
                    ORDER BY ab.start_date DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $activeBoarders = $stmt->fetchAll();
                foreach ($activeBoarders as $boarder) {
                    $notifications[] = $boarder;
                }
            } catch (Exception $e) {
                error_log("Active boarders query error: " . $e->getMessage());
            }
            
            // 13. Payment Status Changes (confirmed payments - important for revenue tracking)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        p.payment_id as event_id,
                        'payment_confirmed' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        p.payment_amount,
                        p.payment_status,
                        p.updated_at as event_time,
                        'Payment Confirmed' as title,
                        CONCAT('Payment of ₱', FORMAT(p.payment_amount, 2), ' confirmed from ', CONCAT(r.first_name, ' ', r.last_name)) as message,
                        p.payment_status as status,
                        '#28a745' as icon_color,
                        'check-circle' as icon_name
                    FROM payments p
                    JOIN users u ON p.user_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE p.payment_status = 'Confirmed'
                    AND (p.updated_at != p.payment_date OR p.updated_at IS NOT NULL)
                    ORDER BY p.updated_at DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $paymentConfirmed = $stmt->fetchAll();
                foreach ($paymentConfirmed as $payment) {
                    $notifications[] = $payment;
                }
            } catch (Exception $e) {
                error_log("Payment confirmed query error: " . $e->getMessage());
            }
            
            // 14. New Maintenance Requests (recent)
            try {
                $stmt = $db->prepare("
                    SELECT 
                        mr.maintenance_id as event_id,
                        'maintenance_request' as event_type,
                        CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as user_name,
                        r.email,
                        mr.title,
                        mr.priority,
                        mr.status,
                        mr.created_at as event_time,
                        'New Maintenance Request' as title,
                        CONCAT('Maintenance request: ', mr.title, ' (Priority: ', COALESCE(mr.priority, 'Normal'), ')') as message,
                        mr.status as status,
                        CASE 
                            WHEN mr.priority = 'Urgent' THEN '#dc3545'
                            WHEN mr.priority = 'High' THEN '#fd7e14'
                            ELSE '#17a2b8'
                        END as icon_color,
                        'tools' as icon_name
                    FROM maintenance_requests mr
                    JOIN users u ON mr.boarder_id = u.user_id
                    JOIN registrations r ON u.reg_id = r.id
                    ORDER BY mr.created_at DESC
                    LIMIT 500
                ");
                $stmt->execute();
                $maintenanceRequests = $stmt->fetchAll();
                foreach ($maintenanceRequests as $request) {
                    $notifications[] = $request;
                }
            } catch (Exception $e) {
                error_log("Maintenance requests query error: " . $e->getMessage());
            }
            
            // Sort all notifications by event_time (most recent first)
            // Remove any notifications without event_time, then sort
            $notifications = array_filter($notifications, function($notif) {
                return !empty($notif['event_time']) && $notif['event_time'] !== '0000-00-00 00:00:00';
            });
            
            usort($notifications, function($a, $b) {
                $timeA = isset($a['event_time']) ? strtotime($a['event_time']) : 0;
                $timeB = isset($b['event_time']) ? strtotime($b['event_time']) : 0;
                
                // If times are equal, maintain order
                if ($timeA == $timeB) {
                    return 0;
                }
                
                // Sort descending (newest first)
                return $timeB - $timeA;
            });
            
            // Store total count before applying limit
            $total_count = count($notifications);
            
            // Apply limit and offset (but with higher default limit to show more)
            // If limit is very high (>= 1000), show all notifications
            if ($limit >= 1000) {
                // Only apply offset for pagination, but return all results
                $notifications = array_slice($notifications, $offset);
            } else {
                $notifications = array_slice($notifications, $offset, $limit);
            }
            
            // Format time ago for display
            foreach ($notifications as &$notif) {
                if (isset($notif['event_time'])) {
                    $notif['time_ago'] = self::timeAgo($notif['event_time']);
                } else {
                    $notif['time_ago'] = 'Recently';
                }
            }
            
            return [
                'success' => true,
                'data' => $notifications,
                'total' => $total_count
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Calculate time ago from timestamp
     * Returns: "Just now", "X minutes ago", "X hours ago", "X days ago", "X months ago", "X years ago"
     */
    private static function timeAgo($datetime) {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === 'null') {
            return 'Recently';
        }
        
        try {
            $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
            $created = new DateTime($datetime, new DateTimeZone('Asia/Manila'));
            
            // Calculate difference
            $diff = $now->diff($created);
            
            // Calculate total seconds difference
            $totalSeconds = ($diff->days * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
            
            if ($totalSeconds < 0) {
                // If negative, it means created_at is in the future (shouldn't happen)
                return 'Just now';
            } elseif ($totalSeconds < 60) {
                return 'Just now';
            } elseif ($diff->days > 0) {
                // Calculate months and years
                $totalDays = $diff->days;
                if ($totalDays >= 365) {
                    $years = floor($totalDays / 365);
                    return $years . ($years == 1 ? ' year ago' : ' years ago');
                } elseif ($totalDays >= 30) {
                    $months = floor($totalDays / 30);
                    return $months . ($months == 1 ? ' month ago' : ' months ago');
                } else {
                    return $diff->days . ($diff->days == 1 ? ' day ago' : ' days ago');
                }
            } elseif ($diff->h > 0) {
                return $diff->h . ($diff->h == 1 ? ' hour ago' : ' hours ago');
            } elseif ($diff->i > 0) {
                return $diff->i . ($diff->i == 1 ? ' minute ago' : ' minutes ago');
            } else {
                return 'Just now';
            }
        } catch (Exception $e) {
            error_log("Error parsing datetime in timeAgo: " . $e->getMessage() . " - Date: " . $datetime);
            // Fallback: try to format the date
            if (!empty($datetime)) {
                $timestamp = strtotime($datetime);
                if ($timestamp !== false) {
                    return date('M d, Y', $timestamp);
                }
            }
            return 'Recently';
        }
    }
    
    /**
     * Create a system notification log (for tracking purposes)
     * This can be stored in a system_logs table if needed
     */
    public static function logSystemEvent($event_type, $event_data) {
        try {
            $db = getDB();
            // This can be implemented if you want to store system events in a dedicated table
            // For now, we generate them on-the-fly from existing data
            return true;
        } catch (Exception $e) {
            error_log("Error logging system event: " . $e->getMessage());
            return false;
        }
    }
}
?>

