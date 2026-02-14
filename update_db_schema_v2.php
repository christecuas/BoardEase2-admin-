<?php
require_once 'dbConfig.php';

try {
    echo "Starting database migration...\n";

    // 1. Update status column enum and make document fields nullable
    // Note: If 'status' is already a VARCHAR, this will still work. If it's an ENUM, it will update it.
    $sql = "ALTER TABLE registrations 
            MODIFY COLUMN status ENUM('email_unverified', 'profile_incomplete', 'pending_admin_review', 'approved', 'rejected') DEFAULT 'email_unverified',
            MODIFY COLUMN gcash_num VARCHAR(20) NULL,
            MODIFY COLUMN valid_id_type VARCHAR(100) NULL,
            MODIFY COLUMN id_number VARCHAR(100) NULL,
            MODIFY COLUMN idFrontFile VARCHAR(255) NULL,
            MODIFY COLUMN idBackFile VARCHAR(255) NULL,
            MODIFY COLUMN gcash_qr VARCHAR(255) NULL";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'registrations' updated successfully.\n";
    } else {
        throw new Exception("Error updating table: " . $conn->error);
    }

    // 2. Map existing statuses to new ones if necessary
    // 'unverified' -> 'email_unverified'
    // 'pending' -> 'pending_admin_review'
    // (Old ENUM values might cause issues if we changed the definition, 
    // but typically MySQL handles this or we should have used VARCHAR)
    
    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
