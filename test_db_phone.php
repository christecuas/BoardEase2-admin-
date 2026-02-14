<?php
// test_db_phone.php
require_once 'dbConfig.php';

$testPhone = "09925311409";
$testEmail = "test_phone_" . time() . "@example.com";

echo "<h2>Database Phone Test</h2>";
echo "Attempting to insert phone string: <strong>$testPhone</strong><br>";

// 1. Check Schema First
echo "<h3>1. Schema Check</h3>";
$result = $conn->query("SHOW COLUMNS FROM registrations LIKE 'phone'");
if ($row = $result->fetch_assoc()) {
    echo "Column Type: <strong>" . $row['Type'] . "</strong><br>";
} else {
    echo "Column 'phone' not found!<br>";
}

// 2. Try Insert
echo "<h3>2. Insert Test</h3>";
$sql = "INSERT INTO registrations (role, first_name, last_name, email, password, phone, status, cb_agreed, created_at) 
        VALUES ('Test', 'Phone', 'Tester', '$testEmail', 'pass', '$testPhone', 'test', '1', NOW())";

if ($conn->query($sql)) {
    $id = $conn->insert_id;
    echo "Insert successful. ID: $id<br>";
    
    // 3. Verify Data
    echo "<h3>3. Verification</h3>";
    $verify = $conn->query("SELECT phone FROM registrations WHERE id = $id");
    $savedPhone = $verify->fetch_assoc()['phone'];
    
    echo "Sent: '$testPhone'<br>";
    echo "Saved: '$savedPhone'<br>";
    
    if ($testPhone === $savedPhone) {
        echo "<h3 style='color: green'>RESULT: SUCCESS. Database supports full phone number.</h3>";
        echo "Conclusion: The issue is likely in the Android App (sending wrong data).";
    } else {
        echo "<h3 style='color: red'>RESULT: FAILED. Data was altered/truncated.</h3>";
        echo "Conclusion: The Database is still modifying the input (Check Triggers or confirm Schema isn't INT).";
    }
} else {
    echo "Insert failed: " . $conn->error;
}
?>
