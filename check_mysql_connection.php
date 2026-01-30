<?php
/**
 * Check if MySQL is running and accessible
 * Returns 0 if MySQL is running, 1 if not
 * 
 * Usage: php check_mysql_connection.php
 * Exit code: 0 = MySQL running, 1 = MySQL not running
 */

// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query
    $pdo->query("SELECT 1");
    
    // MySQL is running
    if (php_sapi_name() === 'cli') {
        echo "MySQL is running and accessible.\n";
        exit(0); // Success
    } else {
        echo json_encode(['success' => true, 'message' => 'MySQL is running']);
    }
    
} catch (PDOException $e) {
    // MySQL is not running or connection failed
    $errorMsg = "MySQL connection failed: " . $e->getMessage();
    
    if (php_sapi_name() === 'cli') {
        echo $errorMsg . "\n";
        echo "Please start MySQL/XAMPP service.\n";
        exit(1); // Failure
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
    }
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>

