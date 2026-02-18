<?php
// Enable error reporting and logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Comprehensive Activity Notification System
// Tracks and sends notifications for all user activities and interactions
require_once 'notification_helper.php';
require_once 'db_helper.php';
require_once 'notification_template_helper.php';

class ActivityNotifications {
    
    /**
     * Booking Activity Notifications
     */
    public static function notifyBookingCreated($owner_id, $booking_details) {
        error_log("ActivityNotifications::notifyBookingCreated() called");
        error_log("  - owner_id: " . var_export($owner_id, true) . " (type: " . gettype($owner_id) . ")");
        error_log("  - booking_details: " . json_encode($booking_details, JSON_UNESCAPED_SLASHES));
        
        if (empty($owner_id) || !is_numeric($owner_id)) {
            error_log("ActivityNotifications::notifyBookingCreated() ERROR: Invalid owner_id");
            return [
                'success' => false,
                'message' => 'Invalid owner_id',
                'data' => null
            ];
        }
        
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('booking_created', [
            'boarder_name' => $booking_details['boarder_name'] ?? $booking_details['tenant_name'] ?? 'a boarder',
            'room_name' => $booking_details['room_name'] ?? 'a room'
        ], 'New Booking Request', 'You have a new booking request from {boarder_name} for {room_name}');
        
        $title = $template['title'];
        $message = $template['message'];
        
        error_log("  - title: $title");
        error_log("  - message: $message");
        error_log("  - Calling NotificationHelper::createNotification()...");
        
        $result = NotificationHelper::createNotification($owner_id, $title, $message, 'booking', true);
        
        error_log("  - NotificationHelper::createNotification() returned: " . json_encode($result, JSON_UNESCAPED_SLASHES));
        
        return $result;
    }
    
    public static function notifyBookingApproved($boarder_id, $booking_details) {
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('booking_approved', [
            'room_name' => $booking_details['room_name'] ?? 'the room'
        ], 'Booking Approved', 'Your booking request for {room_name} has been approved!');
        
        // Manually create notification with custom data to force refresh if app is in foreground
        $result = NotificationHelper::createNotification($boarder_id, $template['title'], $template['message'], 'booking', false); // Do not send default FCM
        
        // Send custom FCM that includes both notification AND refresh action
        if ($result['success'] && isset($result['data'])) {
             // We need to fetch the tokens ourselves or modify NotificationHelper. 
             // For safety/speed, let's just rely on the fallback we added in approve_booking.php
             // But re-enabling standard FCM for now to ensure AT LEAST the notification shows.
             return NotificationHelper::createNotification($boarder_id, $template['title'], $template['message'], 'booking', true);
        }
        return $result;
    }
    
    public static function notifyBookingDeclined($boarder_id, $booking_details) {
        // Get template from database
        $reason = !empty($booking_details['reason']) ? " Reason: " . $booking_details['reason'] : "";
        $template = NotificationTemplateHelper::getNotificationMessage('booking_declined', [
            'room_name' => $booking_details['room_name'] ?? 'the room',
            'reason' => $reason
        ], 'Booking Declined', 'Your booking request for {room_name} has been declined.{reason}');
        
        return NotificationHelper::createNotification($boarder_id, $template['title'], $template['message'], 'booking', true);
    }
    
    public static function notifyBookingCancelled($user_id, $booking_details) {
        // Get template from database
        $reason = !empty($booking_details['reason']) ? " Reason: " . $booking_details['reason'] : "";
        $template = NotificationTemplateHelper::getNotificationMessage('booking_cancelled', [
            'room_name' => $booking_details['room_name'] ?? 'the room',
            'reason' => $reason
        ], 'Booking Cancelled', 'Booking for {room_name} has been cancelled.{reason}');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'booking', true);
    }
    
    /**
     * Payment Activity Notifications
     */
    public static function notifyPaymentReceived($user_id, $payment_details) {
        // Get template from database
        $amount = isset($payment_details['amount']) ? number_format($payment_details['amount'], 2) : '0.00';
        $description = isset($payment_details['description']) ? " for " . $payment_details['description'] : "";
        
        $template = NotificationTemplateHelper::getNotificationMessage('payment_received', [
            'amount' => $amount,
            'description' => $description
        ], 'Payment Received', 'Payment of ₱{amount} has been received{description}');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'payment', true);
    }
    
    public static function notifyPaymentCreated($owner_id, $payment_details) {
        // Get template from database
        $amount = isset($payment_details['amount']) ? number_format($payment_details['amount'], 2) : '0.00';
        $description = isset($payment_details['description']) ? " for " . $payment_details['description'] : "";
        
        $template = NotificationTemplateHelper::getNotificationMessage('payment_created', [
            'amount' => $amount,
            'description' => $description
        ], 'New Payment Pending', 'A new payment of ₱{amount} is pending{description}');
        
        return NotificationHelper::createNotification($owner_id, $template['title'], $template['message'], 'payment', true);
    }
    
    public static function notifyPaymentStatusUpdated($user_id, $payment_details) {
        // Get template from database
        $status = $payment_details['status'] ?? 'updated';
        $amount = isset($payment_details['amount']) ? number_format($payment_details['amount'], 2) : '0.00';
        
        $template = NotificationTemplateHelper::getNotificationMessage('payment_status_updated', [
            'amount' => $amount,
            'status' => ucfirst($status)
        ], 'Payment Status Updated', 'Your payment of ₱{amount} status has been updated to: {status}');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'payment', true);
    }
    
    public static function notifyPaymentOverdue($user_id, $payment_details) {
        // Get template from database
        $amount = isset($payment_details['amount']) ? number_format($payment_details['amount'], 2) : '0.00';
        
        $template = NotificationTemplateHelper::getNotificationMessage('payment_overdue', [
            'amount' => $amount
        ], 'Payment Overdue', 'Your payment of ₱{amount} is overdue. Please settle it as soon as possible.');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'payment', true);
    }
    
    /**
     * Message Activity Notifications
     */
    public static function notifyNewMessage($receiver_id, $message_details) {
        // Get template from database
        $sender_name = $message_details['sender_name'] ?? 'Someone';
        $message_preview = isset($message_details['message']) ? 
            (strlen($message_details['message']) > 50 ? substr($message_details['message'], 0, 50) . '...' : $message_details['message']) : 
            'sent you a message';
        
        $template = NotificationTemplateHelper::getNotificationMessage('message_new', [
            'sender_name' => $sender_name,
            'message_preview' => $message_preview
        ], 'New Message from ' . $sender_name, 'New message from {sender_name}: {message_preview}');
        
        return NotificationHelper::createNotification($receiver_id, $template['title'], $template['message'], 'general', true);
    }
    
    public static function notifyNewGroupMessage($receiver_id, $group_message_details) {
        // Get template from database
        $group_name = $group_message_details['group_name'] ?? 'Group';
        $sender_name = $group_message_details['sender_name'] ?? 'Someone';
        
        $template = NotificationTemplateHelper::getNotificationMessage('message_group', [
            'group_name' => $group_name,
            'sender_name' => $sender_name
        ], $group_name, 'New message in {group_name} from {sender_name}');
        
        return NotificationHelper::createNotification($receiver_id, $template['title'], $template['message'], 'general', true);
    }
    
    /**
     * Maintenance Activity Notifications
     */
    public static function notifyMaintenanceRequest($owner_id, $maintenance_details) {
        // Get template from database
        $boarder_name = $maintenance_details['boarder_name'] ?? 'A boarder';
        $room_name = $maintenance_details['room_name'] ?? 'a room';
        $title_text = $maintenance_details['title'] ?? 'Maintenance needed';
        
        $template = NotificationTemplateHelper::getNotificationMessage('maintenance_request', [
            'boarder_name' => $boarder_name,
            'room_name' => $room_name,
            'title' => $title_text
        ], 'New Maintenance Request', '{boarder_name} has submitted a maintenance request for {room_name}: {title}');
        
        return NotificationHelper::createNotification($owner_id, $template['title'], $template['message'], 'maintenance', true);
    }
    
    public static function notifyMaintenanceStatusUpdated($boarder_id, $maintenance_details) {
        // Get template from database
        $status = $maintenance_details['status'] ?? 'updated';
        
        $template = NotificationTemplateHelper::getNotificationMessage('maintenance_status_updated', [
            'status' => ucfirst($status)
        ], 'Maintenance Status Updated', 'Maintenance request status updated to: {status}');
        
        return NotificationHelper::createNotification($boarder_id, $template['title'], $template['message'], 'maintenance', true);
    }
    
    public static function notifyMaintenanceCompleted($boarder_id, $maintenance_details) {
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('maintenance_completed', [
        ], 'Maintenance Completed', 'Your maintenance request has been completed.');
        
        return NotificationHelper::createNotification($boarder_id, $template['title'], $template['message'], 'maintenance', true);
    }
    
    public static function notifyMaintenanceFeedbackReceived($owner_id, $maintenance_details) {
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('maintenance_feedback', [
        ], 'Maintenance Feedback', 'Feedback received for maintenance request.');
        
        return NotificationHelper::createNotification($owner_id, $template['title'], $template['message'], 'maintenance', true);
    }
    
    /**
     * Review Activity Notifications
     */
    public static function notifyNewReview($owner_id, $review_details) {
        $title = "New Review";
        $boarder_name = $review_details['boarder_name'] ?? 'A boarder';
        $rating = isset($review_details['rating']) ? $review_details['rating'] . ' stars' : '';
        $bh_name = $review_details['boarding_house_name'] ?? 'your boarding house';
        $message = $boarder_name . " has left a " . $rating . " review for " . $bh_name;
        return NotificationHelper::createNotification($owner_id, $title, $message, 'general', true);
    }
    
    public static function notifyOwnerResponse($boarder_id, $review_details) {
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('announcement_owner_response', [
        ], 'Owner Response', 'Owner responded to your review.');
        
        return NotificationHelper::createNotification($boarder_id, $template['title'], $template['message'], 'general', true);
    }
    
    /**
     * Registration Activity Notifications
     * IMPORTANT: These notifications are user-specific and should ONLY be sent to the specific user_id provided
     */
    public static function notifyRegistrationApproved($user_id, $registration_details) {
        // Validate user_id
        if (empty($user_id) || !is_numeric($user_id)) {
            error_log("ActivityNotifications::notifyRegistrationApproved: Invalid user_id: " . var_export($user_id, true));
            return [
                'success' => false,
                'message' => 'Invalid user_id',
                'data' => null
            ];
        }
        
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('registration_approved', [
        ], 'Registration Approved', 'Your registration has been approved! You can now login to your account.');
        
        // Log that this is being sent to a specific user only
        error_log("ActivityNotifications: Sending registration approval notification to user_id=$user_id only (not to all users)");
        
        // Use 'registration_approved' type for better tracking
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'registration_approved', true);
    }
    
    public static function notifyRegistrationRejected($user_id, $registration_details) {
        // Validate user_id
        if (empty($user_id) || !is_numeric($user_id)) {
            error_log("ActivityNotifications::notifyRegistrationRejected: Invalid user_id: " . var_export($user_id, true));
            return [
                'success' => false,
                'message' => 'Invalid user_id',
                'data' => null
            ];
        }
        
        // Get template from database
        $reason = !empty($registration_details['reason']) ? " Reason: " . $registration_details['reason'] : "";
        $template = NotificationTemplateHelper::getNotificationMessage('registration_rejected', [
            'reason' => $reason
        ], 'Registration Rejected', 'Your registration has been rejected. Please contact support for more information.{reason}');
        
        // Log that this is being sent to a specific user only
        error_log("ActivityNotifications: Sending registration rejection notification to user_id=$user_id only (not to all users)");
        
        // Use 'registration_rejected' type for better tracking
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'registration_rejected', true);
    }
    
    /**
     * Account Security Notifications
     */
    public static function notifyPasswordChanged($user_id, $account_details) {
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('security_password_changed', [
        ], 'Password Changed', 'Your password has been successfully changed.');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'general', true);
    }
    
    public static function notifyEmailChanged($user_id, $account_details) {
        // Get template from database
        $template = NotificationTemplateHelper::getNotificationMessage('security_email_changed', [
        ], 'Email Changed', 'Your email address has been successfully changed.');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'general', true);
    }
    
    public static function notifyProfileUpdated($user_id, $profile_details) {
        $title = "Profile Updated";
        $message = "Your profile information has been successfully updated.";
        return NotificationHelper::createNotification($user_id, $title, $message, 'general', false); // Don't send FCM for profile updates
    }
    
    /**
     * Announcement Notifications
     * NOTE: This method sends to a SINGLE user. For sending to all users, 
     * call this method in a loop for each user (see get_admin_notifications.php)
     */
    public static function notifyNewAnnouncement($user_id, $announcement_details) {
        // Validate user_id
        if (empty($user_id) || !is_numeric($user_id)) {
            error_log("ActivityNotifications::notifyNewAnnouncement: Invalid user_id: " . var_export($user_id, true));
            return [
                'success' => false,
                'message' => 'Invalid user_id',
                'data' => null
            ];
        }
        
        // Get template from database
        $announcementTitle = $announcement_details['title'] ?? 'New Announcement';
        // Support both 'content' and 'message' keys
        $content = $announcement_details['content'] ?? $announcement_details['message'] ?? 'New announcement available';
        $type = $announcement_details['type'] ?? 'announcement';
        
        $template = NotificationTemplateHelper::getNotificationMessage('announcement_new', [
            'title' => $announcementTitle,
            'message' => $content
        ], $announcementTitle, '{title}: {message}');
        
        // This sends to a single user only - for announcements to all users,
        // the caller (get_admin_notifications.php) should loop through all users
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], $type, true);
    }
    
    /**
     * Favorite Activity Notifications (Optional - can be disabled if too many)
     */
    public static function notifyBoardingHouseAddedToFavorites($owner_id, $favorite_details) {
        // Only notify if enabled in settings - this can generate many notifications
        $title = "New Favorite";
        $boarder_name = $favorite_details['boarder_name'] ?? 'Someone';
        $bh_name = $favorite_details['boarding_house_name'] ?? 'your boarding house';
        $message = $boarder_name . " has added " . $bh_name . " to their favorites.";
        return NotificationHelper::createNotification($owner_id, $title, $message, 'general', false); // Disabled by default
    }
    /**
     * Community Chat Notifications
     */
    public static function notifyAddedToCommunity($user_id, $details) {
        $group_name = $details['group_name'] ?? 'Community Chat';
        
        $template = NotificationTemplateHelper::getNotificationMessage('community_welcome', [
            'group_name' => $group_name
        ], 'Welcome to ' . $group_name, 'You have been added to {group_name}.');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'general', true);
    }
    
    public static function notifyRemovedFromCommunity($user_id, $details) {
        $group_name = $details['group_name'] ?? 'Community Chat';
        
        $template = NotificationTemplateHelper::getNotificationMessage('community_removed', [
            'group_name' => $group_name
        ], 'Removed from Community', 'You have been removed from {group_name} because your stay has ended.');
        
        return NotificationHelper::createNotification($user_id, $template['title'], $template['message'], 'general', true);
    }
}
?>

