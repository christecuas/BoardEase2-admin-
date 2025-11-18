<?php
/**
 * Script to add user accounts for all approved registrations that don't have user accounts
 * Run this script to fix any missing user accounts
 */

require_once 'dbConfig.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "========================================\n";
echo "Fix Missing User Accounts\n";
echo "========================================\n\n";

// Find all approved registrations without user accounts
$sql = "SELECT r.id, r.email, r.first_name, r.middle_name, r.last_name, r.role, r.status
        FROM registrations r
        LEFT JOIN users u ON u.reg_id = r.id
        WHERE r.status = 'approved' AND u.user_id IS NULL
        ORDER BY r.id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("ERROR: Failed to execute query: " . $conn->error . "\n");
}

if ($result->num_rows === 0) {
    echo "✓ No missing user accounts found.\n";
    echo "All approved registrations already have user accounts.\n";
    $conn->close();
    exit(0);
}

echo "Found " . $result->num_rows . " approved registration(s) without user accounts:\n\n";

$successCount = 0;
$errorCount = 0;
$errors = [];

while ($row = $result->fetch_assoc()) {
    $regId = $row['id'];
    $email = $row['email'];
    $name = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
    
    echo "Processing Registration ID: $regId\n";
    echo "  Email: $email\n";
    echo "  Name: $name\n";
    echo "  Role: " . $row['role'] . "\n";
    
    // Double-check if user exists
    $checkSql = "SELECT user_id FROM users WHERE reg_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        echo "  ✗ ERROR: Failed to prepare check: " . $conn->error . "\n\n";
        $errorCount++;
        $errors[] = "Registration ID $regId: Failed to prepare check - " . $conn->error;
        continue;
    }
    
    $checkStmt->bind_param("i", $regId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existing = $checkResult->fetch_assoc();
        echo "  ✓ User already exists (user_id: " . $existing['user_id'] . ")\n\n";
        $checkStmt->close();
        continue;
    }
    $checkStmt->close();
    
    // Create user account
    $insertSql = "INSERT INTO users (reg_id, profile_picture, status) VALUES (?, NULL, 'Active')";
    $insertStmt = $conn->prepare($insertSql);
    
    if (!$insertStmt) {
        echo "  ✗ ERROR: Failed to prepare INSERT: " . $conn->error . "\n\n";
        $errorCount++;
        $errors[] = "Registration ID $regId: Failed to prepare INSERT - " . $conn->error;
        continue;
    }
    
    $insertStmt->bind_param("i", $regId);
    
    if ($insertStmt->execute()) {
        $userId = $conn->insert_id;
        
        if ($userId > 0) {
            // Verify
            $verifySql = "SELECT user_id, reg_id, status FROM users WHERE user_id = ?";
            $verifyStmt = $conn->prepare($verifySql);
            $verifyStmt->bind_param("i", $userId);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            
            if ($verifyResult->num_rows > 0) {
                $user = $verifyResult->fetch_assoc();
                echo "  ✓ SUCCESS: User account created (user_id: " . $user['user_id'] . ")\n\n";
                $successCount++;
            } else {
                echo "  ✗ ERROR: User created but verification failed!\n\n";
                $errorCount++;
                $errors[] = "Registration ID $regId: User created but not found in database";
            }
            $verifyStmt->close();
        } else {
            echo "  ✗ ERROR: insert_id is 0!\n\n";
            $errorCount++;
            $errors[] = "Registration ID $regId: insert_id is 0";
        }
    } else {
        echo "  ✗ ERROR: Failed to execute INSERT: " . $insertStmt->error . "\n";
        echo "    MySQL Error: " . $conn->error . "\n\n";
        $errorCount++;
        $errors[] = "Registration ID $regId: " . $insertStmt->error . " (MySQL: " . $conn->error . ")";
    }
    
    $insertStmt->close();
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  Successfully added: $successCount user account(s)\n";
echo "  Errors: $errorCount\n";
echo "========================================\n";

if ($errorCount > 0 && count($errors) > 0) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

$conn->close();





