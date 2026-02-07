<?php
require_once 'dbConfig.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if updated_at column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM bookings LIKE 'updated_at'");
    $stmt->execute();
    $column = $stmt->fetch();

    if (!$column) {
        // Add updated_at column
        $sql = "ALTER TABLE bookings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $pdo->exec($sql);
        echo "Successfully added 'updated_at' column to bookings table.";
    } else {
        // Check if it has ON UPDATE CURRENT_TIMESTAMP behavior (hard to check precisely with query, but we can Modify it to be sure)
        $sql = "ALTER TABLE bookings MODIFY COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $pdo->exec($sql);
        echo "'updated_at' column already exists. Ensured it has ON UPDATE behavior.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
