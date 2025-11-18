<?php
/**
 * Web-accessible script to fix missing user accounts for approved registrations
 * Access this file via browser: http://localhost/BoardEase2/fix_missing_user_web.php
 */

require_once 'dbConfig.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Missing User Accounts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .info {
            color: #2196F3;
        }
        .warning {
            color: #FF9800;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #45a049;
        }
        .stats {
            margin: 20px 0;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Missing User Accounts</h1>
        
        <?php
        $action = $_GET['action'] ?? 'check';
        $registrationId = $_GET['reg_id'] ?? null;
        
        if ($action === 'fix' && $registrationId) {
            // Fix specific registration
            echo "<h2>Fixing Registration ID: $registrationId</h2>";
            
            // Check if registration exists
            $checkRegSql = "SELECT id, email, first_name, last_name, role, status FROM registrations WHERE id = ?";
            $checkRegStmt = $conn->prepare($checkRegSql);
            $checkRegStmt->bind_param("i", $registrationId);
            $checkRegStmt->execute();
            $regResult = $checkRegStmt->get_result();
            
            if ($regResult->num_rows === 0) {
                echo "<p class='error'>Registration ID $registrationId not found!</p>";
            } else {
                $registration = $regResult->fetch_assoc();
                echo "<p><strong>Registration:</strong> " . $registration['email'] . " (" . $registration['first_name'] . " " . $registration['last_name'] . ")</p>";
                
                // Check if user exists
                $checkUserSql = "SELECT user_id FROM users WHERE reg_id = ?";
                $checkUserStmt = $conn->prepare($checkUserSql);
                $checkUserStmt->bind_param("i", $registrationId);
                $checkUserStmt->execute();
                $userResult = $checkUserStmt->get_result();
                
                if ($userResult->num_rows > 0) {
                    $existing = $userResult->fetch_assoc();
                    echo "<p class='info'>User already exists: user_id = " . $existing['user_id'] . "</p>";
                } else {
                    // Create user
                    $insertSql = "INSERT INTO users (reg_id, profile_picture, status) VALUES (?, NULL, 'Active')";
                    $insertStmt = $conn->prepare($insertSql);
                    $insertStmt->bind_param("i", $registrationId);
                    
                    if ($insertStmt->execute()) {
                        $userId = $conn->insert_id;
                        echo "<p class='success'>✓ User account created successfully! user_id = $userId</p>";
                    } else {
                        echo "<p class='error'>✗ Failed to create user: " . $insertStmt->error . "</p>";
                    }
                    $insertStmt->close();
                }
                $checkUserStmt->close();
            }
            $checkRegStmt->close();
            
            echo "<br><a href='?action=check'><button>Back to List</button></a>";
            
        } elseif ($action === 'fixall') {
            // Fix all missing users
            echo "<h2>Fixing All Missing User Accounts</h2>";
            
            $sql = "SELECT r.id, r.email, r.first_name, r.last_name, r.role
                    FROM registrations r
                    LEFT JOIN users u ON u.reg_id = r.id
                    WHERE r.status = 'approved' AND u.user_id IS NULL
                    ORDER BY r.id DESC";
            
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $successCount = 0;
                $errorCount = 0;
                
                echo "<p>Found " . $result->num_rows . " registration(s) without user accounts.</p>";
                echo "<pre>";
                
                while ($row = $result->fetch_assoc()) {
                    $regId = $row['id'];
                    
                    // Check again
                    $checkSql = "SELECT user_id FROM users WHERE reg_id = ?";
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param("i", $regId);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        echo "Registration ID $regId: User already exists\n";
                        $checkStmt->close();
                        continue;
                    }
                    $checkStmt->close();
                    
                    // Create user
                    $insertSql = "INSERT INTO users (reg_id, profile_picture, status) VALUES (?, NULL, 'Active')";
                    $insertStmt = $conn->prepare($insertSql);
                    $insertStmt->bind_param("i", $regId);
                    
                    if ($insertStmt->execute()) {
                        $userId = $conn->insert_id;
                        echo "✓ Registration ID $regId: User created (user_id: $userId)\n";
                        $successCount++;
                    } else {
                        echo "✗ Registration ID $regId: Failed - " . $insertStmt->error . "\n";
                        $errorCount++;
                    }
                    $insertStmt->close();
                }
                
                echo "</pre>";
                echo "<div class='stats'>";
                echo "<strong>Summary:</strong><br>";
                echo "Successfully added: $successCount user account(s)<br>";
                echo "Errors: $errorCount<br>";
                echo "</div>";
            } else {
                echo "<p class='success'>No missing user accounts found!</p>";
            }
            
            echo "<br><a href='?action=check'><button>Back to List</button></a>";
            
        } else {
            // Show list of missing users
            echo "<h2>Approved Registrations Without User Accounts</h2>";
            
            $sql = "SELECT r.id, r.email, r.first_name, r.last_name, r.role, r.status, r.created_at
                    FROM registrations r
                    LEFT JOIN users u ON u.reg_id = r.id
                    WHERE r.status = 'approved' AND u.user_id IS NULL
                    ORDER BY r.id DESC";
            
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                echo "<p>Found <strong>" . $result->num_rows . "</strong> approved registration(s) without user accounts:</p>";
                echo "<table border='1' cellpadding='10' cellspacing='0' style='width:100%; border-collapse:collapse;'>";
                echo "<tr style='background:#4CAF50; color:white;'>";
                echo "<th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Created</th><th>Action</th>";
                echo "</tr>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['email'] . "</td>";
                    echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
                    echo "<td>" . $row['role'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "<td><a href='?action=fix&reg_id=" . $row['id'] . "'>Fix</a></td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                echo "<br><a href='?action=fixall'><button>Fix All</button></a>";
            } else {
                echo "<p class='success'>✓ No missing user accounts found. All approved registrations have user accounts!</p>";
            }
        }
        
        $conn->close();
        ?>
    </div>
</body>
</html>





