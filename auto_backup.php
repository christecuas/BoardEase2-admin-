<?php
// Auto backup functionality
// This file can be run via cron job for automatic database backups

// Include database configuration
require_once 'dbConfig.php';

// Function to backup database (same as backup_database.php)
function backupDatabase($host, $username, $password, $database, $tables = '*') {
    try {
        // Create connection
        $connection = new mysqli($host, $username, $password, $database);
        
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        // Set charset
        $connection->set_charset("utf8");
        
        // Get all table names
        $tables = array();
        $result = $connection->query("SHOW TABLES");
        
        while($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        // Start output
        $output = "-- Database Backup\n";
        $output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Database: " . $database . "\n\n";
        $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $output .= "SET AUTOCOMMIT = 0;\n";
        $output .= "START TRANSACTION;\n";
        $output .= "SET time_zone = \"+00:00\";\n\n";
        
        // Disable foreign key checks
        $output .= "/*!40101 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
        $output .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
        $output .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
        $output .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
        $output .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
        $output .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
        $output .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
        $output .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
        
        // Loop through tables
        foreach($tables as $table) {
            // Get table structure
            $output .= "--\n";
            $output .= "-- Table structure for table `$table`\n";
            $output .= "--\n\n";
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $createTable = $connection->query("SHOW CREATE TABLE `$table`");
            $createTableRow = $createTable->fetch_array();
            $output .= $createTableRow[1] . ";\n\n";
            
            // Get table data
            $output .= "--\n";
            $output .= "-- Dumping data for table `$table`\n";
            $output .= "--\n\n";
            
            $data = $connection->query("SELECT * FROM `$table`");
            
            if ($data->num_rows > 0) {
                $output .= "LOCK TABLES `$table` WRITE;\n";
                $output .= "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;\n";
                
                while($row = $data->fetch_assoc()) {
                    $output .= "INSERT INTO `$table` VALUES (";
                    $values = array();
                    foreach($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $connection->real_escape_string($value) . "'";
                        }
                    }
                    $output .= implode(',', $values) . ");\n";
                }
                
                $output .= "/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n";
                $output .= "UNLOCK TABLES;\n\n";
            }
        }
        
        // Re-enable foreign key checks
        $output .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
        $output .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
        $output .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
        $output .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
        $output .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
        $output .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
        $output .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
        $output .= "COMMIT;\n";
        
        $connection->close();
        
        return $output;
        
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Create backup directory if it doesn't exist
$backupDir = 'backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Get database configuration
$host = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Set error logging to a specific file
$logFile = __DIR__ . '/logs/backup.log';
$logDir = dirname($logFile);
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Function to log messages
function logBackup($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    error_log($message); // Also log to PHP error log
}

// Log start of backup
logBackup("Auto backup process started");

// Generate backup
try {
    $backup = backupDatabase($host, $username, $password, $database);
    
    if (strpos($backup, 'Error:') === 0) {
        // Check if it's a connection error
        $errorMsg = $backup;
        if (strpos($backup, 'refused') !== false || 
            strpos($backup, 'Connection refused') !== false ||
            strpos($backup, 'target machine actively refused') !== false) {
            $errorMsg .= " - MySQL/XAMPP may not be running. Please start MySQL service.";
        }
        
        // Log error
        logBackup("Auto backup FAILED: " . $errorMsg);
        echo "ERROR: " . $errorMsg . PHP_EOL;
        exit(1);
    }
    
    // Save backup to file
    $filename = $backupDir . '/boardease_auto_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    $result = file_put_contents($filename, $backup);
    
    if ($result === false) {
        logBackup("Auto backup FAILED: Could not write backup file: $filename");
        echo "ERROR: Could not write backup file" . PHP_EOL;
        exit(1);
    }
    
    // Get file size
    $fileSize = filesize($filename);
    $fileSizeMB = round($fileSize / (1024 * 1024), 2);
    
    // Log success
    logBackup("Auto backup created successfully: $filename (Size: {$fileSizeMB} MB)");
    echo "Auto backup completed successfully: $filename (Size: {$fileSizeMB} MB)" . PHP_EOL;
    
    // Clean up old backups (keep only last 7 days)
    $files = glob($backupDir . '/boardease_auto_backup_*.sql');
    $cutoff = time() - (7 * 24 * 60 * 60); // 7 days ago
    $deletedCount = 0;
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            if (unlink($file)) {
                logBackup("Deleted old backup: $file");
                $deletedCount++;
            } else {
                logBackup("WARNING: Could not delete old backup: $file");
            }
        }
    }
    
    if ($deletedCount > 0) {
        logBackup("Cleaned up $deletedCount old backup file(s)");
    }
    
    logBackup("Auto backup process completed successfully");
    exit(0);
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    
    // Check if it's a connection error
    if (strpos($errorMsg, 'refused') !== false || 
        strpos($errorMsg, 'Connection refused') !== false ||
        strpos($errorMsg, 'target machine actively refused') !== false) {
        $errorMsg .= " - MySQL/XAMPP may not be running. Please start MySQL service.";
    }
    
    logBackup("Auto backup FAILED with exception: " . $errorMsg);
    echo "ERROR: " . $errorMsg . PHP_EOL;
    exit(1);
}
?>

