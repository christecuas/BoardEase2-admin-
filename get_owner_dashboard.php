<?php
header("Content-Type: application/json");
include 'dbConfig.php';

$response = [];

// --- Validate user_id ---
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
if ($user_id <= 0) {
    echo json_encode(["error" => "Invalid user_id"]);
    exit;
}

// --- Owner name (JOIN users + registrations) ---
$sqlOwner = "SELECT CONCAT(r.first_name, ' ', r.last_name) AS fullname
             FROM users u
             INNER JOIN registrations r ON u.reg_id = r.id
             WHERE u.user_id = ?";
$stmtOwner = $conn->prepare($sqlOwner);
$stmtOwner->bind_param("i", $user_id);
$stmtOwner->execute();
$resOwner = $stmtOwner->get_result();
$ownerRow = $resOwner->fetch_assoc();
$response["owner_name"] = $ownerRow ? $ownerRow["fullname"] : "Owner";

// --- Count listings ---
$sqlCount = "SELECT COUNT(*) AS total FROM boarding_houses WHERE user_id = ?";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param("i", $user_id);
$stmtCount->execute();
$resCount = $stmtCount->get_result();
$rowCount = $resCount->fetch_assoc();
$response["listings_count"] = $rowCount ? intval($rowCount["total"]) : 0;

// --- Count boarders (confirmed bookings in owner's boarding houses) ---
$sqlBoarders = "SELECT COUNT(DISTINCT b.user_id) AS total 
                FROM bookings b 
                JOIN room_units ru ON b.room_id = ru.room_id 
                JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id 
                JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id 
                WHERE bh.user_id = ? AND b.booking_status = 'Confirmed'";
$stmtBoarders = $conn->prepare($sqlBoarders);
$stmtBoarders->bind_param("i", $user_id);
$stmtBoarders->execute();
$resBoarders = $stmtBoarders->get_result();
$rowBoarders = $resBoarders->fetch_assoc();
$response["boarders_count"] = $rowBoarders ? intval($rowBoarders["total"]) : 0;

// --- Placeholder (para dili maguba ang Android code nga nag expect ani) ---
$response["views_count"] = 0;
$response["popular_listing"] = [
    "bh_name"    => "N/A",
    "visits"     => 0,
    "image_path" => ""
];

// --- Output JSON ---
echo json_encode($response, JSON_UNESCAPED_SLASHES);
?>








