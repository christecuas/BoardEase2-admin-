<?php
require_once 'dbConfig.php';

// Check users table columns
$result = $conn->query("SHOW COLUMNS FROM users");
echo "<h3>USERS Table Columns:</h3>";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}

// Check registrations table columns
$result = $conn->query("SHOW COLUMNS FROM registrations");
echo "<h3>REGISTRATIONS Table Columns:</h3>";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
