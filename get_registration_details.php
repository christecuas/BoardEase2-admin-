<?php
// get_registration_details.php

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Database connection
    require_once 'dbConfig.php';

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get POST data
    $registrationId = $_POST['registration_id'] ?? null;

    if (!$registrationId) {
        throw new Exception("Missing registration ID");
    }

    // Get registration details
    $sql = "SELECT id, role, first_name, middle_name, last_name, birth_date, phone, address, email, 
                   gcash_num, valid_id_type, id_number, idFrontFile, idBackFile, gcash_qr, 
                   status, email_verified, created_at
            FROM registrations 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $registrationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Registration not found");
    }
    
    $registration = $result->fetch_assoc();
    $stmt->close();

    // Format the response
    $response = array(
        "success" => true,
        "registration" => array(
            "id" => $registration['id'],
            "role" => $registration['role'],
            "first_name" => $registration['first_name'],
            "middle_name" => $registration['middle_name'],
            "last_name" => $registration['last_name'],
            "full_name" => trim($registration['first_name'] . ' ' . $registration['middle_name'] . ' ' . $registration['last_name']),
            "birth_date" => $registration['birth_date'],
            "phone" => $registration['phone'],
            "address" => $registration['address'],
            "email" => $registration['email'],
            "gcash_num" => $registration['gcash_num'],
            "valid_id_type" => $registration['valid_id_type'],
            "id_number" => $registration['id_number'],
            "id_front_file" => $registration['idFrontFile'],
            "id_back_file" => $registration['idBackFile'],
            "gcash_qr" => $registration['gcash_qr'],
            "status" => $registration['status'],
            "email_verified" => $registration['email_verified'],
            "created_at" => $registration['created_at']
        )
    );

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Get registration details error: " . $e->getMessage());
    $response = array(
        "success" => false,
        "message" => "Error fetching registration details: " . $e->getMessage()
    );
    echo json_encode($response);
}

$conn->close();
?>







