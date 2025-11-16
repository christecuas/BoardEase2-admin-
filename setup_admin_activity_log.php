<?php
/**
 * Setup script to create admin_activity_log table
 * Run this script once to create the table
 */
require_once 'dbConfig.php';

try {
    // Check if table already exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
    
    if ($checkTable && $checkTable->num_rows > 0) {
        echo "✅ admin_activity_log table already exists.\n";
        echo "📊 Checking table structure...\n";
        
        // Check if table has correct structure
        $describe = $conn->query("DESCRIBE admin_activity_log");
        if ($describe) {
            echo "✅ Table structure is correct.\n";
        }
        exit(0);
    }
    
    echo "📝 Creating admin_activity_log table...\n";
    
    // Create admin_activity_log table (without foreign key first)
    $sql = "CREATE TABLE IF NOT EXISTS admin_activity_log (
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
    
    if ($conn->query($sql)) {
        echo "✅ admin_activity_log table created successfully!\n";
        
        // Try to add foreign key constraint if admin_accounts table exists
        $checkAdminTable = $conn->query("SHOW TABLES LIKE 'admin_accounts'");
        if ($checkAdminTable && $checkAdminTable->num_rows > 0) {
            // Check if foreign key already exists
            $checkFK = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_activity_log' AND CONSTRAINT_NAME LIKE '%admin_id%'");
            if (!$checkFK || $checkFK->num_rows == 0) {
                // Add foreign key constraint
                $fkSql = "ALTER TABLE admin_activity_log ADD CONSTRAINT fk_admin_activity_admin_id FOREIGN KEY (admin_id) REFERENCES admin_accounts(admin_id) ON DELETE CASCADE";
                if ($conn->query($fkSql)) {
                    echo "✅ Foreign key constraint added successfully!\n";
                } else {
                    echo "⚠️  Warning: Could not add foreign key constraint: " . $conn->error . "\n";
                    echo "   (Table created but foreign key constraint failed - this is okay)\n";
                }
            } else {
                echo "✅ Foreign key constraint already exists.\n";
            }
        } else {
            echo "⚠️  Warning: admin_accounts table does not exist. Foreign key constraint skipped.\n";
        }
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    echo "\n🎉 Database setup complete! Admin activity logging is now enabled.\n";
    echo "📝 All admin activities will now be logged to the admin_activity_log table.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>

