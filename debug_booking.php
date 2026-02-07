<?php
require_once 'dbConfig.php';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = 64");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($row);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
