-- Create notification_templates table
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_key VARCHAR(100) UNIQUE NOT NULL,
    template_title VARCHAR(255) NOT NULL,
    template_message TEXT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default templates
INSERT INTO notification_templates (template_key, template_title, template_message, notification_type) VALUES
('booking_created', 'New Booking Request', 'You have a new booking request from {tenant_name} for {room_name}', 'booking'),
('booking_approved', 'Booking Approved', 'Your booking request for {room_name} has been approved!', 'booking'),
('booking_declined', 'Booking Declined', 'Your booking request for {room_name} has been declined.{reason}', 'booking'),
('booking_cancelled', 'Booking Cancelled', 'Booking for {room_name} has been cancelled.', 'booking'),
('payment_received', 'Payment Received', 'Payment of ₱{amount} has been received{description}', 'payment'),
('payment_created', 'New Payment Pending', 'A new payment of ₱{amount} is pending{description}', 'payment'),
('payment_status_updated', 'Payment Status Updated', 'Your payment of ₱{amount} status has been updated to: {status}', 'payment'),
('payment_overdue', 'Payment Overdue', 'Your payment of ₱{amount} is overdue. Please settle it as soon as possible.', 'payment'),
('maintenance_request', 'New Maintenance Request', '{boarder_name} has submitted a maintenance request for {room_name}: {title}', 'maintenance'),
('maintenance_status_updated', 'Maintenance Status Updated', 'Maintenance request status updated to: {status}', 'maintenance'),
('maintenance_completed', 'Maintenance Completed', 'Your maintenance request has been completed.', 'maintenance'),
('maintenance_feedback', 'Maintenance Feedback', 'Feedback received for maintenance request.', 'maintenance'),
('announcement_new', 'New Announcement', '{title}: {message}', 'announcement'),
('announcement_owner_response', 'Owner Response', 'Owner responded to your review.', 'announcement'),
('registration_approved', 'Registration Approved', 'Your registration has been approved! You can now login to your account.', 'registration'),
('registration_rejected', 'Registration Rejected', 'Your registration has been rejected. Please contact support for more information.', 'registration'),
('message_new', 'New Message', 'New message from {sender_name}: {message_preview}', 'message'),
('message_group', 'New Group Message', 'New message in {group_name} from {sender_name}', 'message'),
('security_password_changed', 'Password Changed', 'Your password has been successfully changed.', 'security'),
('security_email_changed', 'Email Changed', 'Your email address has been successfully changed.', 'security')
ON DUPLICATE KEY UPDATE template_message = VALUES(template_message);



