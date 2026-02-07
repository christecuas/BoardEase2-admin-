<?php
require_once 'db_connect.php';

function describeTable($pdo, $tableName) {
    echo "Metainfo for table: $tableName\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM $tableName");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Default'] . "\n";
    }
    echo "------------------------------------------------\n";
}

try {
    describeTable($pdo, 'payments');
    describeTable($pdo, 'payment_breakdowns');
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
