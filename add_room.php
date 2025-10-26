<?php
// add_room.php
header('Content-Type: application/json');
include 'dbConfig.php'; // $conn = mysqli connection

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

try {
    $bh_id = isset($_POST['bh_id']) ? intval($_POST['bh_id']) : 0;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $room_name = isset($_POST['title']) ? trim($_POST['title']) : '';
    $room_description = isset($_POST['room_description']) ? trim($_POST['room_description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 0;
    $total_rooms = isset($_POST['total_rooms']) ? intval($_POST['total_rooms']) : 0;

    // Debug logging
    error_log("DEBUG: Received parameters:");
    error_log("bh_id: " . $bh_id);
    error_log("category: " . $category);
    error_log("room_name: " . $room_name);
    error_log("room_description: '" . $room_description . "'");
    error_log("price: " . $price);
    error_log("capacity: " . $capacity);
    error_log("total_rooms: " . $total_rooms);

    if ($bh_id <= 0 || empty($room_name) || $capacity <= 0 || $price <= 0 || $total_rooms <= 0) {
        echo json_encode(['error' => 'Missing or invalid parameters']);
        exit;
    }

    $conn->begin_transaction();

    // Insert room
    $stmt = $conn->prepare("INSERT INTO boarding_house_rooms 
        (bh_id, room_category, room_name, price, capacity, room_description, total_rooms) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    // Fixed parameter binding: issdiis (i=int, s=string, s=string, d=double, i=int, s=string, i=int)
    $stmt->bind_param("issdisi", 
    $bh_id, 
    $category, 
    $room_name, 
    $price, 
    $capacity, 
    $room_description, 
    $total_rooms
);

    $stmt->execute();
    $bhr_id = $stmt->insert_id;
    $stmt->close();

    // Auto-create room units
    $words = explode(' ', $room_name);
    $prefix = '';
    foreach ($words as $w) {
        if (!empty($w)) $prefix .= strtoupper($w[0]);
    }

    $stmtUnit = $conn->prepare("INSERT INTO room_units (bhr_id, room_number, status) VALUES (?, ?, 'Available')");
    for ($i = 1; $i <= $total_rooms; $i++) {
        $room_number = $prefix . '-' . $i;
        $stmtUnit->bind_param("is", $bhr_id, $room_number);
        $stmtUnit->execute();
    }
    $stmtUnit->close();

    $conn->commit();

    echo json_encode(['success' => true, 'bhr_id' => $bhr_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>