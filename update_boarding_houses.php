<?php
header('Content-Type: application/json');

include 'dbConfig.php';

$bh_id = intval($_POST['bh_id']);
$bh_name = trim($_POST['bh_name']);
$bh_address = trim($_POST['bh_address']);
$bh_description = trim($_POST['bh_description']);
$bh_rules = trim($_POST['bh_rules']);
$number_of_bathroom = intval($_POST['number_of_bathroom']);
$area = floatval($_POST['area']);
$build_year = intval($_POST['build_year']);
$status = 'Active';

$sql = "UPDATE boarding_houses SET bh_name=?, bh_address=?, bh_description=?, bh_rules=?, number_of_bathroom=?, area=?, build_year=?, status=? WHERE bh_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssiidsi", $bh_name, $bh_address, $bh_description, $bh_rules, $number_of_bathroom, $area, $build_year, $status, $bh_id);

if ($stmt->execute()) {
    echo json_encode(["success" => "Updated successfully"]);
} else {
    echo json_encode(["error" => "Update failed"]);
}

$stmt->close();
$conn->close();
?>