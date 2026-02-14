<?php
// get_pending_registrations.php

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Database connection
    // Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get pending registrations
    // Use DATE_FORMAT to ensure created_at is in a consistent format
    $sql = "SELECT id, role, first_name, middle_name, last_name, suffix, birth_date, phone, address, email, 
                   gcash_num, valid_id_type, id_number, idFrontFile, idBackFile, gcash_qr, 
                   status, 
                   created_at,
                   DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as formatted_created_at
            FROM registrations 
            WHERE status = 'pending_admin_review' 
            ORDER BY created_at DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $registrations = array();
    while ($row = $result->fetch_assoc()) {
        // Construct full name with suffix
        $fullName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        if (!empty($row['suffix'])) {
            $fullName .= ' ' . $row['suffix'];
        }
        
        // Use formatted_created_at if available, otherwise use created_at
        $createdAt = !empty($row['formatted_created_at']) ? $row['formatted_created_at'] : $row['created_at'];
        
        $registrations[] = array(
            "id" => $row['id'],
            "role" => $row['role'],
            "first_name" => $row['first_name'],
            "middle_name" => $row['middle_name'],
            "last_name" => $row['last_name'],
            "suffix" => $row['suffix'],
            "full_name" => $fullName,
            "birth_date" => $row['birth_date'],
            "phone" => $row['phone'],
            "address" => $row['address'],
            "email" => $row['email'],
            "gcash_num" => $row['gcash_num'],
            "valid_id_type" => $row['valid_id_type'],
            "id_number" => $row['id_number'],
            "id_front_file" => $row['idFrontFile'],
            "id_back_file" => $row['idBackFile'],
            "gcash_qr" => $row['gcash_qr'],
            "status" => $row['status'],
            "created_at" => $createdAt, // Use formatted date
            "created_at_timestamp" => strtotime($createdAt) // Also include timestamp for easier JS parsing
        );
    }

    $response = array(
        "success" => true,
        "data" => $registrations,
        "count" => count($registrations)
    );

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Get pending registrations error: " . $e->getMessage());
    $response = array(
        "success" => false,
        "message" => "Error retrieving pending registrations: " . $e->getMessage()
    );
    echo json_encode($response);
}

$conn->close();
?>
