<?php
// check_triggers.php
require_once 'dbConfig.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($triggers);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
