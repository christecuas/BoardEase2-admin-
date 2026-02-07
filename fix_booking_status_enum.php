<?php
// Include database configuration
require_once 'dbConfig.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fixing Booking Status Enum</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.<br>";

    // Current ENUM values (inferred): 'Pending', 'Confirmed', 'Cancelled', 'Completed'
    // New ENUM values to add: 'Approved', 'Declined'
    
    // We will modify the column to be VARCHAR(50) first to be safe, OR just update the ENUM.
    // robust approach: Update ENUM to include all necessary statuses.
    
    $sql = "ALTER TABLE bookings MODIFY COLUMN booking_status ENUM('Pending', 'Approved', 'Confirmed', 'Cancelled', 'Declined', 'Completed') DEFAULT 'Pending'";
    
    echo "Executing query: <code>$sql</code><br>";
    
    $pdo->exec($sql);
    
    echo "<h3 style='color: green;'>Success! Database updated.</h3>";
    echo "<p>The <code>booking_status</code> column now accepts: <strong>Pending, Approved, Confirmed, Cancelled, Declined, Completed</strong>.</p>";
    echo "<p>You can now try approving a booking again.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Database Error:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
