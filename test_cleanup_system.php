<?php
// test_cleanup_system.php
// This script creates a test unverified account to test the cleanup system

require_once 'dbConfig.php';

echo "Testing Cleanup System\n";
echo "=====================\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create a test unverified account with old timestamp
    $testEmail = 'test_cleanup_' . time() . '@example.com';
    $oldTimestamp = date('Y-m-d H:i:s', strtotime('-35 minutes')); // 35 minutes ago
    
    $sql = "INSERT INTO registrations (role, first_name, last_name, email, password, status, created_at) 
            VALUES ('Boarder', 'Test User', 'Test User', ?, 'test123', 'unverified', ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $testEmail, $oldTimestamp);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo "✅ Created test unverified account:\n";
        echo "   ID: $userId\n";
        echo "   Email: $testEmail\n";
        echo "   Created: $oldTimestamp\n";
        echo "   Status: unverified\n\n";
        
        // Also create a verification record
        $verificationSql = "INSERT INTO email_verifications (user_id, email, verification_code, expiry_time, created_at) 
                           VALUES (?, ?, '123456', ?, ?)";
        $verificationStmt = $conn->prepare($verificationSql);
        $verificationStmt->bind_param("isss", $userId, $testEmail, $oldTimestamp, $oldTimestamp);
        $verificationStmt->execute();
        $verificationStmt->close();
        
        echo "✅ Created associated verification record\n\n";
        
        echo "🧪 Test Setup Complete!\n";
        echo "The cleanup script should delete this account within 5 minutes.\n";
        echo "Check the logs/cleanup.log file to see the cleanup process.\n\n";
        
        echo "To manually test the cleanup, run:\n";
        echo "php cleanup_unverified_accounts.php\n";
        
    } else {
        echo "❌ Failed to create test account: " . $stmt->error . "\n";
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
