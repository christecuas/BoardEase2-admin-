<?php
// cleanup_unverified_accounts.php
// This script deletes unverified accounts that are older than 30 minutes

require_once 'dbConfig.php';

// Create logs directory if it doesn't exist
$logDir = 'logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/cleanup.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage;
}

writeLog("=== CLEANUP SCRIPT STARTED ===");

try {
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        writeLog("Database connection failed: " . $conn->connect_error);
        exit(1);
    }
    
    writeLog("Database connection established");
    
    // First, let's check if we need to update the status enum to include 'unverified'
    $result = $conn->query("SHOW COLUMNS FROM registrations LIKE 'status'");
    $statusColumn = $result->fetch_assoc();
    
    if (strpos($statusColumn['Type'], 'unverified') === false) {
        writeLog("Updating status enum to include 'unverified'");
        $conn->query("ALTER TABLE registrations MODIFY COLUMN status ENUM('unverified','pending','approved','rejected') DEFAULT 'unverified'");
        writeLog("Status enum updated successfully");
    }
    
    $currentTime = date('Y-m-d H:i:s');
    $cutoffTime = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    
    writeLog("Current time: $currentTime");
    writeLog("Looking for accounts older than: $cutoffTime");
    
    // Find unverified accounts older than 30 minutes
    $stmt = $conn->prepare("
        SELECT r.id, r.email, r.first_name, r.created_at, r.idFrontFile, r.idBackFile, r.gcash_qr
        FROM registrations r 
        WHERE r.status = 'unverified' 
        AND r.created_at < ?
        ORDER BY r.created_at ASC
    ");
    $stmt->bind_param("s", $cutoffTime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $accountsToDelete = [];
    while ($row = $result->fetch_assoc()) {
        $accountsToDelete[] = $row;
    }
    $stmt->close();
    
    $accountCount = count($accountsToDelete);
    writeLog("Found $accountCount accounts to delete");
    
    if ($accountCount > 0) {
        writeLog("Accounts to be deleted:");
        foreach ($accountsToDelete as $account) {
            writeLog("Will delete: ID={$account['id']}, Email={$account['email']}, Name={$account['first_name']}, Created={$account['created_at']}");
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $deletedRegistrations = 0;
            $deletedVerifications = 0;
            $deletedFiles = 0;
            
            foreach ($accountsToDelete as $account) {
                $userId = $account['id'];
                
                // Delete associated verification records
                $verificationStmt = $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?");
                $verificationStmt->bind_param("i", $userId);
                $verificationStmt->execute();
                $deletedVerifications += $verificationStmt->affected_rows;
                $verificationStmt->close();
                
                // Delete uploaded files
                $filesToDelete = [
                    $account['idFrontFile'],
                    $account['idBackFile'], 
                    $account['gcash_qr']
                ];
                
                foreach ($filesToDelete as $file) {
                    if ($file && file_exists($file)) {
                        if (unlink($file)) {
                            $deletedFiles++;
                            writeLog("Deleted file: $file");
                        } else {
                            writeLog("Failed to delete file: $file");
                        }
                    }
                }
                
                // Delete the registration record
                $registrationStmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
                $registrationStmt->bind_param("i", $userId);
                $registrationStmt->execute();
                $deletedRegistrations += $registrationStmt->affected_rows;
                $registrationStmt->close();
            }
            
            // Commit transaction
            $conn->commit();
            
            writeLog("Deleted $deletedRegistrations registration records");
            writeLog("Deleted $deletedVerifications verification records");
            writeLog("Deleted $deletedFiles uploaded files");
            writeLog("=== CLEANUP COMPLETED SUCCESSFULLY ===");
            writeLog("Total accounts deleted: $accountCount");
            
        } catch (Exception $e) {
            $conn->rollback();
            writeLog("Transaction rolled back due to error: " . $e->getMessage());
            throw $e;
        }
        
    } else {
        writeLog("No accounts found for deletion");
    }
    
    $conn->close();
    writeLog("Database connection closed");
    
} catch (Exception $e) {
    writeLog("Cleanup script error: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

writeLog("=== CLEANUP SCRIPT FINISHED ===");
?>
