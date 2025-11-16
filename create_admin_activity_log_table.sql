-- Create admin_activity_log table to track all admin activities
CREATE TABLE IF NOT EXISTS admin_activity_log (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'password_change', 'email_change', 'status_change', 'user_approved', 'user_rejected', 'user_created', 'user_updated', 'user_deleted', 'system_change', 'other') DEFAULT 'other',
    activity_title VARCHAR(255) NOT NULL,
    activity_description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_accounts(admin_id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


