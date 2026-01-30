<?php
// reset_all_activity.php
// FORCE RESET: Sets all users to "Offline"
require_once 'dbConfig.php';

header('Content-Type: text/plain');

// Set all device tokens to inactive
$sql = "UPDATE device_tokens SET is_active = 0";

if ($conn->query($sql) === TRUE) {
    echo "SUCCESS: All users have been reset to OFFLINE.\n";
    echo "Rows updated: " . $conn->affected_rows . "\n\n";
    echo "Now, only users who OPEN the app will turn Green (Active).";
} else {
    echo "Error resetting activity: " . $conn->error;
}

$conn->close();
?>
