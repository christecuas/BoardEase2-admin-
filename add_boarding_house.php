<?php
header('Content-Type: application/json');
include 'dbConfig.php'; // $conn is defined here

// Enable mysqli exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

try {
    // Get POST data safely
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $bh_name = isset($_POST['bh_name']) ? trim($_POST['bh_name']) : '';
    $bh_address = isset($_POST['bh_address']) ? trim($_POST['bh_address']) : '';
    $bh_description = isset($_POST['bh_description']) ? trim($_POST['bh_description']) : '';
    $bh_rules = isset($_POST['bh_rules']) ? trim($_POST['bh_rules']) : '';
    $number_of_bathroom = isset($_POST['number_of_bathroom']) ? intval($_POST['number_of_bathroom']) : 0;
    $area = isset($_POST['area']) ? trim($_POST['area']) : '';
    $build_year = isset($_POST['build_year']) ? trim($_POST['build_year']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';

    // Basic validation
    if ($user_id <= 0 || empty($bh_name) || empty($bh_address) || $number_of_bathroom <= 0) {
        echo json_encode(["error" => "Missing required fields."]);
        exit;
    }

    // Prepare SQL statement
    $sql = "INSERT INTO boarding_houses 
        (user_id, bh_name, bh_address, bh_description, bh_rules, number_of_bathroom, area, build_year, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssisss", 
        $user_id, 
        $bh_name, 
        $bh_address, 
        $bh_description, 
        $bh_rules, 
        $number_of_bathroom, 
        $area, 
        $build_year, 
        $status
    );

    // Execute and return result
    $stmt->execute();
    $bh_id = $stmt->insert_id;
    echo json_encode(["success" => $bh_id]);

} catch (mysqli_sql_exception $e) {
    // Catch SQL errors and return as JSON
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
