-- Table to track payment reminder notifications sent
-- This prevents duplicate reminders from being sent on the same day

CREATE TABLE IF NOT EXISTS `payment_reminder_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `breakdown_id` int(11) NOT NULL COMMENT 'Payment breakdown ID',
  `user_id` int(11) NOT NULL COMMENT 'User who should receive the reminder',
  `reminder_type` enum('5_days_before','4_days_before','3_days_before','2_days_before','1_day_before','due_date') NOT NULL COMMENT 'Type of reminder',
  `due_date` date NOT NULL COMMENT 'Due date of the payment',
  `reminder_date` date NOT NULL COMMENT 'Date when reminder was sent',
  `notif_id` int(11) DEFAULT NULL COMMENT 'Notification ID created',
  `fcm_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether FCM push notification was sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `unique_reminder` (`breakdown_id`, `reminder_type`, `reminder_date`),
  KEY `breakdown_id` (`breakdown_id`),
  KEY `user_id` (`user_id`),
  KEY `reminder_date` (`reminder_date`),
  KEY `due_date` (`due_date`),
  CONSTRAINT `fk_reminder_breakdown` FOREIGN KEY (`breakdown_id`) REFERENCES `payment_breakdowns` (`breakdown_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reminder_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks payment reminder notifications to prevent duplicates';

