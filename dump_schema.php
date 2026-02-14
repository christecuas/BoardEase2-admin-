<?php
header('Content-Type: text/plain');
require_once 'dbConfig.php';

try {
    $stmt = $conn->query("DESCRIBE registrations");
    while ($row = $stmt->fetch_assoc()) {
        echo $row['Field'] . "\t" . $row['Type'] . "\t" . $row['Null'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
