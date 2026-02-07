<?php
require_once 'dbConfig.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.<br>";

    // Update room_units ENUM
    // Adding 'Reserved' and 'Available(Partially Occupied)'
    // Existing (inferred): 'Available', 'Occupied', 'Unavailable', 'Partially Occupied'
    
    // We will include both 'Partially Occupied' (legacy) and 'Available(Partially Occupied)' (new request) just in case,
    // although the user specifically asked for 'Available(Partially Occupied)'.
    
    $sql = "ALTER TABLE room_units MODIFY COLUMN status ENUM('Available', 'Occupied', 'Unavailable', 'Partially Occupied', 'Reserved', 'Available(Partially Occupied)') DEFAULT 'Available'";
    
    echo "Executing query: <code>$sql</code><br>";
    $pdo->exec($sql);
    
    echo "<h3 style='color: green;'>Success! room_units table updated.</h3>";
    echo "<p>The <code>status</code> column now accepts key statuses.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Database Error:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
