<?php
// cleanup_incomplete_profiles.php
// This script deletes accounts with 'profile_incomplete' status that are older than 1 MONTH

require_once __DIR__ . '/dbConfig.php';

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/cleanup_incomplete.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage;
}

writeLog("=== CLEANUP INCOMPLETE PROFILES STARTED ===");

try {
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        writeLog("Database connection failed: " . $conn->connect_error);
        exit(1);
    }
    
    writeLog("Database connection established");
    
    $currentTime = date('Y-m-d H:i:s');
    $cutoffTime = date('Y-m-d H:i:s', strtotime('-1 month'));
    
    writeLog("Current time: $currentTime");
    writeLog("Looking for 'profile_incomplete' accounts older than: $cutoffTime");
    
    // Find profile_incomplete accounts older than 1 month
    $stmt = $conn->prepare("
        SELECT r.id, r.email, r.first_name, r.created_at, r.idFrontFile, r.idBackFile, r.gcash_qr
        FROM registrations r 
        WHERE r.status = 'profile_incomplete' 
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
    writeLog("Found $accountCount incomplete profiles to delete");
    
    if ($accountCount > 0) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $deletedRegistrations = 0;
            $deletedFiles = 0;
            
            foreach ($accountsToDelete as $account) {
                $userId = $account['id'];
                writeLog("Deleting ID={$account['id']}, Email={$account['email']}");

                // Delete uploaded files
                $filesToDelete = [
                    $account['idFrontFile'],
                    $account['idBackFile'], 
                    $account['gcash_qr']
                ];
                
                // Get business permits to delete
                $permitStmt = $conn->prepare("SELECT permit_file FROM bs_permits WHERE reg_id = ?");
                $permitStmt->bind_param("i", $userId);
                $permitStmt->execute();
                $permitResult = $permitStmt->get_result();
                while ($perm = $permitResult->fetch_assoc()) {
                    $filesToDelete[] = $perm['permit_file'];
                }
                $permitStmt->close();

                // Delete permits record
                $delPermitStmt = $conn->prepare("DELETE FROM bs_permits WHERE reg_id = ?");
                $delPermitStmt->bind_param("i", $userId);
                $delPermitStmt->execute();
                $delPermitStmt->close();

                foreach ($filesToDelete as $file) {
                    if ($file && file_exists($file)) {
                        if (unlink($file)) {
                            $deletedFiles++;
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
            
            writeLog("Deleted $deletedRegistrations incomplete profiles");
            writeLog("Deleted $deletedFiles uploaded files");
            writeLog("=== CLEANUP INCOMPLETE SUCCESSFUL ===");
            
        } catch (Exception $e) {
            $conn->rollback();
            writeLog("Transaction rolled back: " . $e->getMessage());
            throw $e;
        }
        
    } else {
        writeLog("No incomplete profiles found for deletion");
    }
    
    $conn->close();
    
} catch (Exception $e) {
    writeLog("Error: " . $e->getMessage());
    exit(1);
}

writeLog("=== FINISHED ===");
?>
