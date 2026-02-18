<?php
require_once 'dbConfig.php';

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

try {
    echo "Checking group_members table for 'status' column...\n";
    
    // Check if column exists
    $result = $conn->query("SHOW COLUMNS FROM group_members LIKE 'status'");
    
    if ($result->num_rows == 0) {
        echo "Column 'status' does not exist. Adding it...\n";
        
        // Add status column with default 'Active'
        // ENUM values: Active, Removed, Left
        $sql = "ALTER TABLE group_members 
                ADD COLUMN status ENUM('Active', 'Removed', 'Left') NOT NULL DEFAULT 'Active' 
                AFTER gm_role";
        
        if ($conn->query($sql) === TRUE) {
            echo "Successfully added 'status' column to group_members table.\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    } else {
        echo "Column 'status' already exists.\n";
    }
    
    // Verify
    $result = $conn->query("SHOW COLUMNS FROM group_members");
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'status') {
            echo "Verified column: " . $row['Field'] . " (" . $row['Type'] . ") Default: " . $row['Default'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
