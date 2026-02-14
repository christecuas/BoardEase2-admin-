<?php
require_once 'dbConfig.php';

try {
    // 1. Update the 'status' column in 'registrations' table to include new statuses
    // We use ALTER TABLE to modify the ENUM definition
    $sql = "ALTER TABLE registrations MODIFY COLUMN status ENUM(
        'email_unverified', 
        'profile_incomplete', 
        'pending_admin_review', 
        'approved', 
        'rejected'
    ) DEFAULT 'email_unverified'";
    
    if ($conn->query($sql) === TRUE) {
        echo "Successfully updated 'status' column in 'registrations' table.<br>";
    } else {
        throw new Exception("Error updating 'status' column: " . $conn->error);
    }

    // 2. Ensure document fields are nullable (as per actual table structure)
    $sql_docs = "ALTER TABLE registrations 
        MODIFY COLUMN idFrontFile VARCHAR(255) NULL,
        MODIFY COLUMN idBackFile VARCHAR(255) NULL,
        MODIFY COLUMN gcash_qr VARCHAR(255) NULL";
        
    if ($conn->query($sql_docs) === TRUE) {
        echo "Successfully made document fields nullable in 'registrations' table.<br>";
    } else {
        echo "Note: Document fields might already be nullable or were not found.<br>";
    }

    echo "<strong>Database update completed successfully!</strong>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
