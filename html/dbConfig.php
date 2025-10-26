<?php
$servername = "localhost";
$username = "boardease";
$password = "boardease";
$database = "boardease2";

$conn  = new mysqli ($servername, $username, $password, $database);

if($conn -> connect_error){
	die("Connection Failed" . $conn -> connect_error);
}
?>