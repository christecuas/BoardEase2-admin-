<?php
include 'db_config.php'; // your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bh_id = isset($_POST['bh_id']) ? intval($_POST['bh_id']) : 0;

    if ($bh_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid Boarding House ID"]);
        exit;
    }

    // OPTIONAL: delete related images first (if you store them separately)
    $sqlDeleteImages = "DELETE FROM room_images WHERE room_id IN (SELECT room_id FROM room_units WHERE bhr_id = ?)";
    $stmt = $conn->prepare($sqlDeleteImages);
    $stmt->bind_param("i", $bh_id);
    $stmt->execute();
    $stmt->close();

    // OPTIONAL: delete related rooms
    $sqlDeleteRooms = "DELETE FROM room_units WHERE bhr_id = ?";
    $stmt = $conn->prepare($sqlDeleteRooms);
    $stmt->bind_param("i", $bh_id);
    $stmt->execute();
    $stmt->close();

    // Now delete the boarding house itself
    $sqlDeleteBh = "DELETE FROM boarding_houses WHERE bh_id = ?";
    $stmt = $conn->prepare($sqlDeleteBh);
    $stmt->bind_param("i", $bh_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Boarding house deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete boarding house"]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
