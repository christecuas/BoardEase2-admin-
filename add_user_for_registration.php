<?php
require_once 'dbConfig.php';

// Get registration ID from command line or use default
$registrationId = isset($argv[1]) ? (int)$argv[1] : null;

if (!$registrationId) {
    echo "Usage: php add_user_for_registration.php <registration_id>\n";
    echo "Example: php add_user_for_registration.php 87\n";
    exit(1);
}

echo "Adding user account for Registration ID: $registrationId\n";
echo "========================================================\n\n";

// Check if registration exists and is approved
$checkRegSql = "SELECT id, email, first_name, middle_name, last_name, role, status 
                FROM registrations 
                WHERE id = ?";
$checkRegStmt = $conn->prepare($checkRegSql);
$checkRegStmt->bind_param("i", $registrationId);
$checkRegStmt->execute();
$regResult = $checkRegStmt->get_result();

if ($regResult->num_rows === 0) {
    echo "ERROR: Registration ID $registrationId not found!\n";
    $checkRegStmt->close();
    $conn->close();
    exit(1);
}

$registration = $regResult->fetch_assoc();
$checkRegStmt->close();

echo "Registration Details:\n";
echo "  ID: " . $registration['id'] . "\n";
echo "  Email: " . $registration['email'] . "\n";
echo "  Name: " . $registration['first_name'] . " " . ($registration['middle_name'] ?? '') . " " . $registration['last_name'] . "\n";
echo "  Role: " . $registration['role'] . "\n";
echo "  Status: " . $registration['status'] . "\n\n";

if ($registration['status'] !== 'approved') {
    echo "WARNING: Registration status is '" . $registration['status'] . "', not 'approved'.\n";
    echo "Do you want to continue anyway? (This script will create the user account regardless)\n\n";
}

// Check if user already exists
$checkUserSql = "SELECT user_id, reg_id, status FROM users WHERE reg_id = ?";
$checkUserStmt = $conn->prepare($checkUserSql);
$checkUserStmt->bind_param("i", $registrationId);
$checkUserStmt->execute();
$userResult = $checkUserStmt->get_result();

if ($userResult->num_rows > 0) {
    $existingUser = $userResult->fetch_assoc();
    echo "User account already exists:\n";
    echo "  user_id: " . $existingUser['user_id'] . "\n";
    echo "  reg_id: " . $existingUser['reg_id'] . "\n";
    echo "  status: " . $existingUser['status'] . "\n";
    $checkUserStmt->close();
    $conn->close();
    exit(0);
}
$checkUserStmt->close();

// Create user account
echo "Creating user account...\n";
$insertSql = "INSERT INTO users (reg_id, profile_picture, status) VALUES (?, NULL, 'Active')";
$insertStmt = $conn->prepare($insertSql);

if (!$insertStmt) {
    echo "ERROR: Failed to prepare INSERT statement: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

$insertStmt->bind_param("i", $registrationId);

if ($insertStmt->execute()) {
    $userId = $conn->insert_id;
    $affectedRows = $insertStmt->affected_rows;
    
    echo "INSERT executed successfully.\n";
    echo "  insert_id: $userId\n";
    echo "  affected_rows: $affectedRows\n\n";
    
    if ($userId > 0) {
        // Verify the user was created
        $verifySql = "SELECT user_id, reg_id, status FROM users WHERE user_id = ?";
        $verifyStmt = $conn->prepare($verifySql);
        $verifyStmt->bind_param("i", $userId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows > 0) {
            $user = $verifyResult->fetch_assoc();
            echo "✓ SUCCESS: User account created and verified!\n";
            echo "  user_id: " . $user['user_id'] . "\n";
            echo "  reg_id: " . $user['reg_id'] . "\n";
            echo "  status: " . $user['status'] . "\n";
        } else {
            echo "✗ ERROR: User account created but not found in database!\n";
        }
        $verifyStmt->close();
    } else {
        echo "✗ ERROR: insert_id is 0 or negative!\n";
    }
} else {
    echo "ERROR: Failed to execute INSERT: " . $insertStmt->error . "\n";
    echo "MySQL Error: " . $conn->error . "\n";
}

$insertStmt->close();
$conn->close();

echo "\nDone.\n";





