<?php
// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Room Categories (Bed Spacer) ---\n";
    $sql = "SELECT bhr_id, room_name, room_category, capacity FROM boarding_house_rooms WHERE room_category = 'Bed Spacer'";
    $stmt = $pdo->query($sql);
    $bhrIds = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['bhr_id'] . " | Name: " . $row['room_name'] . " | Capacity: " . $row['capacity'] . "\n";
        $bhrIds[] = $row['bhr_id'];
    }

    echo "\n--- Room Units for above Categories ---\n";
    if (!empty($bhrIds)) {
        $inQuery = implode(',', array_fill(0, count($bhrIds), '?'));
        $sql = "SELECT room_id, bhr_id, room_number, status FROM room_units WHERE bhr_id IN ($inQuery)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bhrIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Unit ID: " . $row['room_id'] . " | BHR ID: " . $row['bhr_id'] . " | Number: " . $row['room_number'] . " | Status: '" . $row['status'] . "'\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
