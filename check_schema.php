<?php
// check_schema.php
// Dump schema for payments and payment_breakdowns

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
    
    $tables = ['payments', 'payment_breakdowns'];
    $result = [];

    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result[$table] = $columns;
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
