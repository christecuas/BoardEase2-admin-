<?php
// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'boardease');
define('DB_PASS', 'boardease');
define('DB_NAME', 'boardease2');

// Create connection
$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

$conn = new mysqli($servername, $username, $password, $database);

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}
?>