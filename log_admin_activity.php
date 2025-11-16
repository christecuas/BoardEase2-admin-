<?php
/**
 * Log admin activity to the database
 * This should be called whenever an admin performs an action
 */
function logAdminActivity($admin_id, $activity_type, $activity_title, $activity_description = '', $ip_address = null, $user_agent = null) {
    try {
        // Include dbConfig.php to get $conn
        if (!isset($GLOBALS['conn'])) {
            require_once 'dbConfig.php';
        }
        global $conn;
        
        if (!$conn) {
            error_log("Error: Database connection not available in logAdminActivity");
            return false;
        }
        
        // Get IP address if not provided
        if ($ip_address === null && isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        // Get user agent if not provided
        if ($user_agent === null && isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Check if table exists first (with error handling)
        $checkTable = @$conn->query("SHOW TABLES LIKE 'admin_activity_log'");
        if (!$checkTable || $checkTable->num_rows == 0) {
            // Try to create table if it doesn't exist (without foreign key constraint first)
            $createTableSql = "CREATE TABLE IF NOT EXISTS admin_activity_log (
                activity_id INT PRIMARY KEY AUTO_INCREMENT,
                admin_id INT NOT NULL,
                activity_type ENUM('login', 'logout', 'password_change', 'email_change', 'status_change', 'user_approved', 'user_rejected', 'user_created', 'user_updated', 'user_deleted', 'system_change', 'other') DEFAULT 'other',
                activity_title VARCHAR(255) NOT NULL,
                activity_description TEXT,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_activity_type (activity_type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (@$conn->query($createTableSql)) {
                error_log("Admin activity log table created successfully");
                
                // Try to add foreign key constraint if admin_accounts table exists
                $checkAdminTable = @$conn->query("SHOW TABLES LIKE 'admin_accounts'");
                if ($checkAdminTable && $checkAdminTable->num_rows > 0) {
                    // Check if foreign key already exists
                    $checkFK = @$conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_activity_log' AND CONSTRAINT_NAME LIKE '%admin_id%'");
                    if (!$checkFK || $checkFK->num_rows == 0) {
                        // Add foreign key constraint
                        @$conn->query("ALTER TABLE admin_activity_log ADD CONSTRAINT fk_admin_activity_admin_id FOREIGN KEY (admin_id) REFERENCES admin_accounts(admin_id) ON DELETE CASCADE");
                    }
                }
            } else {
                error_log("Warning: admin_activity_log table does not exist and could not be created: " . $conn->error);
                error_log("Please run setup_admin_activity_log.php to create the table.");
                return false;
            }
        }
        
        // Insert activity log
        $stmt = $conn->prepare("
            INSERT INTO admin_activity_log 
            (admin_id, activity_type, activity_title, activity_description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            error_log("Error preparing statement for admin activity log: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("isssss", $admin_id, $activity_type, $activity_title, $activity_description, $ip_address, $user_agent);
        
        if (!$stmt->execute()) {
            error_log("Error executing admin activity log insert: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $activity_id = $stmt->insert_id;
        $stmt->close();
        
        // Verify the activity was actually inserted
        if ($activity_id > 0) {
            error_log("Admin activity logged successfully: activity_id=$activity_id, admin_id=$admin_id, type=$activity_type, title=$activity_title");
        } else {
            error_log("Warning: Admin activity log insert returned 0 activity_id. admin_id=$admin_id, type=$activity_type");
        }
        
        return $activity_id;
        
    } catch (Exception $e) {
        error_log("Error logging admin activity: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

