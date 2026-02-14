<?php
header('Content-Type: text/plain');
require_once 'dbConfig.php';

try {
    echo "Current Schema for 'phone':\n";
    $stmt = $conn->query("SHOW COLUMNS FROM registrations LIKE 'phone'");
    while ($row = $stmt->fetch_assoc()) {
        echo print_r($row, true) . "\n";
    }

    echo "Altering 'phone' column to VARCHAR(20)...\n";
    $conn->query("ALTER TABLE registrations MODIFY phone VARCHAR(20)");
    echo "Success.\n";

    echo "Current Schema for 'address':\n";
    $stmt = $conn->query("SHOW COLUMNS FROM registrations LIKE 'address'");
    while ($row = $stmt->fetch_assoc()) {
        echo print_r($row, true) . "\n";
    }

    echo "Altering 'address' column to TEXT...\n";
    $conn->query("ALTER TABLE registrations MODIFY address TEXT");
    echo "Success.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
