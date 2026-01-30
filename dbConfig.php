<?php
// Database configuration constants
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

// Set PHP timezone
date_default_timezone_set('Asia/Manila');

// Create connection
$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

$conn = new mysqli($servername, $username, $password, $database);

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}

// Set MySQL timezone to Philippines time
$conn->query("SET time_zone = '+08:00'");
?>