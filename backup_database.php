<?php
// Manual database backup functionality
// This file provides manual backup download functionality

// Include database configuration
require_once 'dbConfig.php';

// Function to backup database
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

// Check if this is a download request
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    // Get database configuration
    $host = DB_HOST;
    $username = DB_USER;
    $password = DB_PASS;
    $database = DB_NAME;
    
    // Generate backup
    $backup = backupDatabase($host, $username, $password, $database);
    
    if (strpos($backup, 'Error:') === 0) {
        // Show error
        echo "<h2>Backup Error</h2>";
        echo "<p style='color: red;'>" . htmlspecialchars($backup) . "</p>";
        echo "<a href='javascript:history.back()'>Go Back</a>";
        exit;
    }
    
    // Set headers for download
    $filename = 'boardease_manual_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($backup));
    
    // Output backup
    echo $backup;
    exit;
}

// If not a download request, show the backup interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Backup</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .backup-btn { 
            padding: 15px 30px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .backup-btn:hover { 
            background: #218838; 
            color: white;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🗄️ Database Backup</h2>
        
        <div class="info">
            <h3>Manual Backup</h3>
            <p>Click the button below to download a complete backup of your database.</p>
            <p><strong>Note:</strong> This will create a SQL file that you can use to restore your database if needed.</p>
        </div>
        
        <a href="?action=backup" class="backup-btn">
            📥 Download Database Backup
        </a>
        
        <div class="info">
            <h3>Automatic Backup Status</h3>
            <p>✅ Automatic backups are running every day at 2:00 AM</p>
            <p>📁 Backup files are stored in the <code>backups/</code> directory</p>
            <p>🗑️ Old backups (older than 7 days) are automatically deleted</p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="index.php" style="color: #007bff; text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
