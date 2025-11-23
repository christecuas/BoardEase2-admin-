<?php
// Notification Template Helper
// Loads and processes notification templates from the database

require_once 'db_helper.php';

class NotificationTemplateHelper {
    
    private static $templates = null;
    
    /**
     * Get all notification templates from database
     */
    public static function getTemplates() {
        if (self::$templates !== null) {
            return self::$templates;
        }
        
        self::$templates = [];
        
        try {
            $db = getDB();
            if (!$db) {
                return self::getDefaultTemplates();
            }
            
            // Check if table exists - getDB() returns MySQLiWrapper, which returns mysqli_result from query()
            $result = $db->query("SHOW TABLES LIKE 'notification_templates'");
            if ($result && $result->num_rows > 0) {
                $stmtResult = $db->query("SELECT template_key, template_title, template_message, notification_type FROM notification_templates");
                if ($stmtResult && $stmtResult->num_rows > 0) {
                    while ($row = $stmtResult->fetch_assoc()) {
                        self::$templates[$row['template_key']] = [
                            'title' => $row['template_title'],
                            'message' => $row['template_message'],
                            'type' => $row['notification_type']
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error loading notification templates: " . $e->getMessage());
        }
        
        // If no templates found, use defaults
        if (empty(self::$templates)) {
            self::$templates = self::getDefaultTemplates();
        }
        
        return self::$templates;
    }
    
    /**
     * Get a specific template by key
     */
    public static function getTemplate($templateKey, $defaultTitle = null, $defaultMessage = null) {
        $templates = self::getTemplates();
        
        if (isset($templates[$templateKey])) {
            return [
                'title' => $templates[$templateKey]['title'],
                'message' => $templates[$templateKey]['message']
            ];
        }
        
        // Return defaults if template not found
        return [
            'title' => $defaultTitle ?? 'Notification',
            'message' => $defaultMessage ?? 'You have a new notification.'
        ];
    }
    
    /**
     * Replace template variables with actual values
     */
    public static function replaceVariables($template, $variables) {
        if (empty($template)) {
            return '';
        }
        
        $result = $template;
        
        // First pass: Replace all provided variables
        // If a variable is empty, replace it with empty string first
        foreach ($variables as $key => $value) {
            $replaceValue = ($value !== null && $value !== '') ? (string)$value : '';
            $result = str_replace('{' . $key . '}', $replaceValue, $result);
        }
        
        // Second pass: Remove any remaining {variable} placeholders that weren't replaced
        // This handles optional variables like {reason}, {description} that might be empty
        $result = preg_replace('/\{[^}]+\}/', '', $result);
        
        // Clean up multiple spaces
        $result = preg_replace('/\s+/', ' ', $result);
        
        // Clean up trailing punctuation and spaces
        $result = trim($result);
        $result = preg_replace('/[\s,;:\.]+$/', '', $result);
        
        return $result;
    }
    
    /**
     * Get formatted notification message using template
     */
    public static function getNotificationMessage($templateKey, $variables = [], $defaultTitle = null, $defaultMessage = null) {
        $template = self::getTemplate($templateKey, $defaultTitle, $defaultMessage);
        
        $title = self::replaceVariables($template['title'], $variables);
        $message = self::replaceVariables($template['message'], $variables);
        
        return [
            'title' => $title,
            'message' => $message
        ];
    }
    
    /**
     * Get default templates (fallback)
     */
    private static function getDefaultTemplates() {
        return [
            'booking_created' => [
                'title' => 'New Booking Request',
                'message' => 'You have a new booking request from {boarder_name} for {room_name}',
                'type' => 'booking'
            ],
            'booking_approved' => [
                'title' => 'Booking Approved',
                'message' => 'Your booking request for {room_name} has been approved!',
                'type' => 'booking'
            ],
            'booking_declined' => [
                'title' => 'Booking Declined',
                'message' => 'Your booking request for {room_name} has been declined.{reason}',
                'type' => 'booking'
            ],
            'booking_cancelled' => [
                'title' => 'Booking Cancelled',
                'message' => 'Booking for {room_name} has been cancelled.',
                'type' => 'booking'
            ],
            'payment_received' => [
                'title' => 'Payment Received',
                'message' => 'Payment of ₱{amount} has been received{description}',
                'type' => 'payment'
            ],
            'payment_created' => [
                'title' => 'New Payment Pending',
                'message' => 'A new payment of ₱{amount} is pending{description}',
                'type' => 'payment'
            ],
            'payment_status_updated' => [
                'title' => 'Payment Status Updated',
                'message' => 'Your payment of ₱{amount} status has been updated to: {status}',
                'type' => 'payment'
            ],
            'payment_overdue' => [
                'title' => 'Payment Overdue',
                'message' => 'Your payment of ₱{amount} is overdue. Please settle it as soon as possible.',
                'type' => 'payment'
            ],
            'maintenance_request' => [
                'title' => 'New Maintenance Request',
                'message' => '{boarder_name} has submitted a maintenance request for {room_name}: {title}',
                'type' => 'maintenance'
            ],
            'maintenance_status_updated' => [
                'title' => 'Maintenance Status Updated',
                'message' => 'Maintenance request status updated to: {status}',
                'type' => 'maintenance'
            ],
            'maintenance_completed' => [
                'title' => 'Maintenance Completed',
                'message' => 'Your maintenance request has been completed.',
                'type' => 'maintenance'
            ],
            'maintenance_feedback' => [
                'title' => 'Maintenance Feedback',
                'message' => 'Feedback received for maintenance request.',
                'type' => 'maintenance'
            ],
            'announcement_new' => [
                'title' => 'New Announcement',
                'message' => '{title}: {message}',
                'type' => 'announcement'
            ],
            'announcement_owner_response' => [
                'title' => 'Owner Response',
                'message' => 'Owner responded to your review.',
                'type' => 'announcement'
            ],
            'registration_approved' => [
                'title' => 'Registration Approved',
                'message' => 'Your registration has been approved! You can now login to your account.',
                'type' => 'registration'
            ],
            'registration_rejected' => [
                'title' => 'Registration Rejected',
                'message' => 'Your registration has been rejected. Please contact support for more information.',
                'type' => 'registration'
            ],
            'message_new' => [
                'title' => 'New Message',
                'message' => 'New message from {sender_name}: {message_preview}',
                'type' => 'message'
            ],
            'message_group' => [
                'title' => 'New Group Message',
                'message' => 'New message in {group_name} from {sender_name}',
                'type' => 'message'
            ],
            'security_password_changed' => [
                'title' => 'Password Changed',
                'message' => 'Your password has been successfully changed.',
                'type' => 'security'
            ],
            'security_email_changed' => [
                'title' => 'Email Changed',
                'message' => 'Your email address has been successfully changed.',
                'type' => 'security'
            ]
        ];
    }
    
    /**
     * Clear template cache (useful after updating templates)
     */
    public static function clearCache() {
        self::$templates = null;
    }
}

